export const adminMenu = [
  { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', href: '/admin/dashboard' },
  { label: 'Authentication', icon: 'i-lucide-shield-check', href: '/admin/auth-logs' },
  { label: 'User Management', icon: 'i-lucide-users', href: '/admin/user-management/students' },
  { label: 'Academic Structure', icon: 'i-lucide-building', href: '/admin/academic-structure/departments' },
  { label: 'Course Management', icon: 'i-lucide-book-open', href: '/admin/course-module/all' },
  { label: 'Assessment', icon: 'i-lucide-clipboard-check', href: '/admin/quizzes' },
  { label: 'Learning & Progress', icon: 'i-lucide-trending-up', href: '/admin/progress' },
  { label: 'Analytics & Reports', icon: 'i-lucide-bar-chart-3', href: '/admin/reports' },
  { label: 'AI Management', icon: 'i-lucide-brain', href: '/admin/ai-rules' },
  { label: 'Communication', icon: 'i-lucide-bell', href: '/admin/notifications/announcements' },
  { label: 'System Settings', icon: 'i-lucide-settings', href: '/admin/settings' },
]

export const teacherMenu = [
  { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', href: '/teacher/dashboard' },
  {
    label: 'My Courses',
    icon: 'i-lucide-book-open',
    href: '/teacher/courses',
    children: [
      { label: 'All Courses', href: '/teacher/courses' },
      { label: 'Create New Course', href: '/teacher/courses/create' },
      { label: 'Draft Courses', href: '/teacher/courses?tab=drafts' },
      { label: 'Pending Approval', href: '/teacher/courses?tab=pending' },
      { label: 'Published Courses', href: '/teacher/courses?tab=published' },
      { label: 'Course Settings', href: '/teacher/courses?tab=settings' },
    ]
  },
  {
    label: 'Content Delivery',
    icon: 'i-lucide-video',
    href: '/teacher/content',
    children: [
      { label: 'Videos', href: '/teacher/content?tab=videos' },
      { label: 'PDFs', href: '/teacher/content?tab=pdfs' },
      { label: 'Slides', href: '/teacher/content?tab=slides' },
      { label: 'Modules & Chapters', href: '/teacher/content?tab=modules' },
      { label: 'Notes & Downloads', href: '/teacher/content?tab=notes' },
      { label: 'AI-Assisted Content', href: '/teacher/content?tab=ai-content' },
      { label: 'Practice Lab', href: '/teacher/content?tab=coding-lab' },
    ]
  },
  {
    label: 'Quiz & Assessment',
    icon: 'i-lucide-clipboard-check',
    href: '/teacher/assessment',
    children: [
      { label: 'Question Bank', href: '/teacher/assessment?tab=questions' },
      { label: 'Quiz', href: '/teacher/assessment?tab=quizzes' },
      { label: 'Pre-Test', href: '/teacher/assessment?tab=pretest' },
      { label: 'Practice Quiz', href: '/teacher/assessment?tab=practice' },
      { label: 'Post-Test', href: '/teacher/assessment?tab=posttest' },
      { label: 'Assignment', href: '/teacher/assessment?tab=assignments' },
      { label: 'Coding Assessment', href: '/teacher/assessment?tab=coding' },
      { label: 'Quiz Results', href: '/teacher/assessment?tab=results' },
    ]
  },
  {
    label: 'Students',
    icon: 'i-lucide-users',
    href: '/teacher/students',
    children: [
      { label: 'Student List', href: '/teacher/students?tab=list' },
      { label: 'Progress', href: '/teacher/students?tab=progress' },
      { label: 'Completion', href: '/teacher/students?tab=completion' },
      { label: 'Quiz Scores', href: '/teacher/students?tab=scores' },
      { label: 'Assignment Scores', href: '/teacher/students?tab=assignment-scores' },
      { label: 'Attendance', href: '/teacher/students?tab=attendance' },
      { label: 'Feedback', href: '/teacher/students?tab=feedback' },
    ]
  },
  {
    label: 'Progress Tracking',
    icon: 'i-lucide-trending-up',
    href: '/teacher/progress',
    children: [
      { label: 'Module Completion', href: '/teacher/progress?tab=modules' },
      { label: 'Learning Time', href: '/teacher/progress?tab=time' },
      { label: 'Weekly Progress', href: '/teacher/progress?tab=weekly' },
      { label: 'Course Progress', href: '/teacher/progress?tab=course-progress' },
      { label: 'Weak Topics', href: '/teacher/progress?tab=weak-topics' },
      { label: 'At-Risk Students', href: '/teacher/progress?tab=at-risk' },
    ]
  },
  { label: 'Reports', icon: 'i-lucide-bar-chart-3', href: '/teacher/reports' },
  { label: 'Discussion & Announcements', icon: 'i-lucide-message-square', href: '/teacher/discussions' },
  { label: 'Calendar', icon: 'i-lucide-calendar', href: '/teacher/calendar' },
  { label: 'Earnings & ABA', icon: 'i-lucide-dollar-sign', href: '/teacher/earnings' },
  { label: 'Notifications', icon: 'i-lucide-bell', href: '/teacher/notifications' },
  { label: 'My Profile', icon: 'i-lucide-user', href: '/teacher/profile' },
]

export const studentMenu = [
  { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', href: '/student/dashboard' },
  { label: 'My Courses', icon: 'i-lucide-book-open', href: '/student/courses' },
  { label: 'Learning Content', icon: 'i-lucide-play-circle', href: '/student/learning' },
  { label: 'Quiz & Assessment', icon: 'i-lucide-clipboard-check', href: '/student/quizzes' },
  { label: 'AI Learning Path', icon: 'i-lucide-brain', href: '/student/ai-path' },
  { label: 'Progress Tracking', icon: 'i-lucide-trending-up', href: '/student/progress' },
  { label: 'Payments (ABA)', icon: 'i-lucide-credit-card', href: '/student/payments' },
  { label: 'Certificates', icon: 'i-lucide-award', href: '/student/certificates' },
  { label: 'Notifications', icon: 'i-lucide-bell', href: '/student/notifications' },
  { label: 'Calendar', icon: 'i-lucide-calendar', href: '/student/calendar' },
  { label: 'Profile', icon: 'i-lucide-user', href: '/student/profile' },
]
