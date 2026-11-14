<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to assign real official representative thumbnails to courses.
     */
    public function up(): void
    {
        $courses = DB::table('courses')->select('id', 'title', 'code')->get();

        foreach ($courses as $c) {
            $t = strtolower($c->title . ' ' . ($c->code ?? ''));
            $thumb = 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80';

            if (str_contains($t, 'business english') || (str_contains($t, 'english') && str_contains($t, 'communication'))) {
                $thumb = 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'english') || str_contains($t, 'grammar')) {
                $thumb = 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'crop') || str_contains($t, 'agronomy')) {
                $thumb = 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'plant') || str_contains($t, 'botany')) {
                $thumb = 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'agriculture') || str_contains($t, 'organic')) {
                $thumb = 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'tourism') || str_contains($t, 'hospitality') || str_contains($t, 'hotel')) {
                $thumb = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'social work') || str_contains($t, 'community') || str_contains($t, 'advocacy')) {
                $thumb = 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'mobile') || str_contains($t, 'flutter')) {
                $thumb = 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'c programming') || str_contains($t, 'structure') || str_contains($t, 'algorithm') || str_contains($t, 'dsa')) {
                $thumb = 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'machine learning') || str_contains($t, 'ai')) {
                $thumb = 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80';
            } elseif (str_contains($t, 'vue') || str_contains($t, 'laravel') || str_contains($t, 'web')) {
                $thumb = 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80';
            }

            DB::table('courses')->where('id', $c->id)->update(['thumbnail' => $thumb]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
