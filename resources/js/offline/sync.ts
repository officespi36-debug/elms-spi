import { db } from './db'

export interface OfflineCourseRecord {
  id: number
  title: string
  description?: string
  thumbnail?: string
  modules?: any[]
  downloadedAt: number
  totalLessons?: number
  sizeEstimateBytes?: number
}

/**
 * Queue an item for background synchronization with Laravel API
 */
export async function queueSync(type: 'progress' | 'quiz-attempt', payload: any) {
  try {
    const d = await db()
    await d.add('syncQueue', { id: crypto.randomUUID(), type, payload })
    if (typeof navigator !== 'undefined' && navigator.onLine) {
      await flushQueue()
    }
  } catch (e) {
    console.warn('Queue sync error:', e)
  }
}

/**
 * Flush all pending sync items to Laravel backend
 */
export async function flushQueue(): Promise<{ success: number; failed: number }> {
  let success = 0
  let failed = 0

  try {
    const d = await db()
    const items = await d.getAll('syncQueue')

    for (const item of items) {
      try {
        const res = await fetch(`/api/v1/sync/${item.type}`, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(item.payload),
        })

        if (res.ok || (res.status >= 200 && res.status < 300)) {
          await d.delete('syncQueue', item.id)
          success++
        } else if (res.status === 400 || res.status === 422) {
          // Validation failed or obsolete item, remove from queue
          await d.delete('syncQueue', item.id)
          failed++
        } else {
          // Temporary server error, keep in queue
          break
        }
      } catch (err) {
        // Network failure, stop queue execution
        break
      }
    }
  } catch (e) {
    console.warn('Flush sync queue failed:', e)
  }

  return { success, failed }
}

/**
 * Save an entire course (modules, lessons, contents) for offline learning
 */
export async function saveCourseForOffline(
  course: any,
  onProgress?: (percent: number, statusText: string) => void
): Promise<boolean> {
  try {
    if (!course || !course.id) return false
    const d = await db()

    onProgress?.(10, 'កំពុងរៀបចំទិន្នន័យវគ្គសិក្សា...')

    // 1. Save Course Metadata
    const courseRecord: OfflineCourseRecord = {
      id: Number(course.id),
      title: course.title || 'Course',
      description: course.description || '',
      thumbnail: course.thumbnail || course.image || '',
      modules: course.modules || [],
      downloadedAt: Date.now(),
      totalLessons: 0,
    }

    let lessonCount = 0
    const lessonsToStore: any[] = []

    if (course.modules && Array.isArray(course.modules)) {
      for (const mod of course.modules) {
        if (mod.lessons && Array.isArray(mod.lessons)) {
          for (const les of mod.lessons) {
            lessonCount++
            lessonsToStore.push({
              ...les,
              id: Number(les.id),
              course_id: Number(course.id),
              module_id: Number(mod.id),
              moduleTitle: mod.title,
            })
          }
        }
      }
    }

    courseRecord.totalLessons = lessonCount
    await d.put('courses', courseRecord)

    onProgress?.(40, 'កំពុងផ្ទុកមេរៀន និងឯកសារ...')

    // 2. Save Lessons
    let processed = 0
    for (const les of lessonsToStore) {
      await d.put('lessons', les)
      processed++
      const progressPercent = 40 + Math.floor((processed / (lessonsToStore.length || 1)) * 50)
      onProgress?.(progressPercent, `បានផ្ទុកមេរៀន ${processed}/${lessonsToStore.length}`)
    }

    // 3. Mark in academic cache for instant SWR loading
    await setCachedData(`course_${course.id}`, course)

    onProgress?.(100, 'បានទាញយកដោយជោគជ័យ!')
    return true
  } catch (err) {
    console.error('Error saving course for offline:', err)
    return false
  }
}

/**
 * Check if a course is cached offline
 */
export async function isCourseCachedOffline(courseId: number | string): Promise<boolean> {
  try {
    const d = await db()
    const rec = await d.get('courses', Number(courseId))
    return !!rec
  } catch {
    return false
  }
}

/**
 * Retrieve offline cached course
 */
export async function getOfflineCourse(courseId: number | string): Promise<any | null> {
  try {
    const d = await db()
    const course = await d.get('courses', Number(courseId))
    if (!course) return null

    // Get all lessons for this course
    const lessons = await d.getAllFromIndex('lessons', 'by-course', Number(courseId))
    
    // Reconstruct course structure if modules exist
    if (course.modules && Array.isArray(course.modules)) {
      course.modules = course.modules.map((mod: any) => ({
        ...mod,
        lessons: lessons.filter((l: any) => Number(l.module_id) === Number(mod.id)),
      }))
    }

    return course
  } catch (e) {
    console.warn('Error fetching offline course:', e)
    return null
  }
}

/**
 * Get all courses saved for offline learning
 */
export async function getAllOfflineCourses(): Promise<OfflineCourseRecord[]> {
  try {
    const d = await db()
    return await d.getAll('courses')
  } catch {
    return []
  }
}

/**
 * Delete a course from offline storage
 */
export async function deleteOfflineCourse(courseId: number | string): Promise<boolean> {
  try {
    const d = await db()
    const cId = Number(courseId)
    await d.delete('courses', cId)
    
    // Delete related lessons
    const lessons = await d.getAllFromIndex('lessons', 'by-course', cId)
    for (const les of lessons) {
      if (les.id) await d.delete('lessons', les.id)
    }

    return true
  } catch {
    return false
  }
}

/**
 * Save user progress locally (works both online and offline)
 */
export async function saveProgressOffline(courseId: number, lessonId: number, completed: boolean = true) {
  try {
    const d = await db()
    const progressKey = `progress_${courseId}_${lessonId}`
    const progressRecord = {
      key: progressKey,
      course_id: courseId,
      lesson_id: lessonId,
      completed,
      updated_at: Date.now(),
    }

    await d.put('progress', progressRecord)

    // Queue for sync with server
    await queueSync('progress', {
      course_id: courseId,
      lesson_id: lessonId,
      completed,
      timestamp: Date.now(),
    })
  } catch (e) {
    console.warn('Failed to save progress offline:', e)
  }
}

/**
 * Cache Academic / General data into IndexedDB
 */
export async function setCachedData(key: string, data: any) {
  try {
    const d = await db()
    await d.put('academicCache', {
      key,
      data,
      cachedAt: Date.now(),
    })
  } catch (e) {
    console.warn('Offline cache write failed:', e)
  }
}

/**
 * Get Cached data from IndexedDB
 */
export async function getCachedData<T = any>(key: string): Promise<T | null> {
  try {
    const d = await db()
    const record = await d.get('academicCache', key)
    return record ? (record.data as T) : null
  } catch (e) {
    return null
  }
}

/**
 * Stale-While-Revalidate (SWR) Pattern:
 * 1. Returns cached local data immediately if available for instantaneous UI render.
 * 2. Fetches fresh data in background from server and updates local cache.
 * 3. Calls onUpdated callback when fresh data arrives.
 */
export async function fetchWithSWR<T = any>(
  key: string,
  fetcher: () => Promise<T>,
  onUpdated?: (freshData: T) => void
): Promise<T | null> {
  const cached = await getCachedData<T>(key)

  // Asynchronously revalidate in background
  if (typeof window !== 'undefined' && navigator.onLine) {
    fetcher()
      .then(async (fresh) => {
        if (fresh !== undefined && fresh !== null) {
          await setCachedData(key, fresh)
          if (onUpdated) {
            onUpdated(fresh)
          }
        }
      })
      .catch((err) => {
        console.warn(`SWR background revalidation failed for ${key}:`, err)
      })
  }

  // If we had cache, return it immediately; otherwise await server response
  if (cached !== null) {
    return cached
  }

  try {
    const fresh = await fetcher()
    if (fresh !== undefined && fresh !== null) {
      await setCachedData(key, fresh)
    }
    return fresh
  } catch (err) {
    return null
  }
}
