<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Public demo embed until real classroom URLs replace it.
     */
    private const DEMO_YOUTUBE = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    public function run(): void
    {
        foreach ($this->catalogue() as $index => $item) {
            $modules = $item['modules'];
            unset($item['modules']);

            $item['sort_order'] = $index;
            $course = Course::query()->updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );

            $course->modules()->delete();

            foreach ($modules as $moduleIndex => $moduleData) {
                $lessons = $moduleData['lessons'];
                unset($moduleData['lessons']);

                $module = Module::query()->create([
                    ...$moduleData,
                    'course_id' => $course->id,
                    'sort_order' => $moduleIndex,
                ]);

                foreach ($lessons as $lessonIndex => $lesson) {
                    Lesson::query()->create([
                        ...$lesson,
                        'module_id' => $module->id,
                        'sort_order' => $lessonIndex,
                    ]);
                }
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            [
                'slug' => 'adobe-photoshop-beginner',
                'title_en' => 'Adobe Photoshop Beginner',
                'title_km' => 'អាដូប៊ី ផូតូស៊ប សម្រាប់អ្នកចាប់ផ្តើម',
                'subtitle_en' => 'From zero to creating professional graphic designs, posters, and photo retouching.',
                'subtitle_km' => 'ពីសូន្យដល់ការបង្កើតការរចនាក្រាហ្វិកជំនាញ ផ្ទាំងផ្សព្វផ្សាយ និងការកែសម្ផស្សរូបថត។',
                'mark' => 'Ps',
                'category' => 'Photoshop',
                'level' => 'Beginner',
                'price' => 0,
                'access_type' => 'free',
                'description_en' => 'Master Adobe Photoshop CC with a practical, step-by-step curriculum. Learn the complete workspace, powerful selection tools, layer masks, Khmer typography integration, and real-world poster creation.',
                'description_km' => 'រៀន Adobe Photoshop CC តាមកម្មវិធីជាក់ស្តែង។ ស្គាល់ workspace, ឧបករណ៍ជ្រើសរើស, layer mask, ពុម្ពអក្សរខ្មែរ និងធ្វើផ្ទាំងផ្សព្វផ្សាយពិតប្រាកដ។',
                'instructor_name' => 'Piseth Yoeurn',
                'instructor_title' => 'Senior Graphic Designer & Educator',
                'is_featured' => true,
                'outcomes' => [
                    ['en' => 'Navigate Photoshop CC with confidence and speed', 'km' => 'ប្រើ Photoshop CC បានរហ័ស និងមានទំនុកចិត្ត'],
                    ['en' => 'Apply precise selection techniques with Object Selection and Pen Tool', 'km' => 'ជ្រើសរើសវត្ថុឱ្យបានត្រឹមត្រូវដោយ Object Selection និង Pen Tool'],
                    ['en' => 'Perform high-end portrait skin retouching and color grading', 'km' => 'កែសម្ផស្ស និងដាក់ពណ៌រូបថតបញ្ឈរ'],
                    ['en' => 'Master layer organization, non-destructive masks, and blending modes', 'km' => 'រៀបចំ layer, mask និង blending mode ដោយមិនបំផ្លាញឯកសារដើម'],
                    ['en' => 'Design modern social media posters with bilingual Khmer typography', 'km' => 'រចនាផ្ទាំងបណ្តាញសង្គមជាភាសាខ្មែរ និងអង់គ្លេស'],
                    ['en' => 'Prepare print-ready CMYK files and optimized web graphics', 'km' => 'នាំចេញឯកសារ CMYK សម្រាប់បោះពុម្ព និងរូបសម្រាប់គេហទំព័រ'],
                ],
                'modules' => [
                    [
                        'title_en' => 'Module 01: Introduction & Essentials',
                        'title_km' => 'ម៉ូឌុល ០១៖ សេចក្តីផ្តើម និងមូលដ្ឋាន',
                        'lessons' => [
                            $this->youtubeLesson(
                                'Lesson 01 — Introduction to Photoshop & Creative Workflow',
                                'មេរៀន ០១ — សេចក្តីផ្តើម Photoshop និងលំហូរការងារ',
                                12,
                                'Welcome to Piseth.Design Classroom. Explore how professional graphic designers use Photoshop for branding, social media, and commercial print.',
                                'សូមស្វាគមន៍មកកាន់ Piseth.Design Classroom។ ស្វែងយល់ពីរបៀបដែលអ្នករចនាប្រើ Photoshop សម្រាប់ម៉ាកយីហោ បណ្តាញសង្គម និងការបោះពុម្ព។',
                            ),
                            $this->youtubeLesson('Lesson 02 — The Beginner Steps & New Document Setup', 'មេរៀន ០២ — ជំហានដំបូង និងបង្កើតឯកសារថ្មី', 15),
                            $this->youtubeLesson('Lesson 03 — Document Settings & Color Modes (RGB vs CMYK)', 'មេរៀន ០៣ — ការកំណត់ឯកសារ និងពណ៌ RGB / CMYK', 14),
                            $this->youtubeLesson('Lesson 04 — Understanding the Workspace & Panels', 'មេរៀន ០៤ — Workspace និងផ្ទាំងឧបករណ៍', 16),
                        ],
                    ],
                    [
                        'title_en' => 'Module 02: Photoshop Interface & Essential Tools',
                        'title_km' => 'ម៉ូឌុល ០២៖ ផ្ទាំងឧបករណ៍ និងឧបករណ៍សំខាន់',
                        'lessons' => [
                            $this->youtubeLesson('Lesson 05 — Navigating the Canvas & Zooming Shortcuts', 'មេរៀន ០៥ — រុករកផ្ទាំងគំនូរ និងផ្លូវកាត់ Zoom', 11),
                            $this->driveLesson('Lesson 06 — Understanding Layers & Layer Masking Secrets', 'មេរៀន ០៦ — Layer និងអាថ៌កំបាំងនៃ Layer Mask', 22),
                            $this->youtubeLesson('Lesson 07 — Selection Tools (Marquee, Lasso, Object Selection)', 'មេរៀន ០៧ — ឧបករណ៍ជ្រើសរើស', 18),
                            $this->youtubeLesson('Lesson 08 — Retouching with Spot Healing Brush & Clone Stamp', 'មេរៀន ០៨ — កែសម្ផស្សដោយ Spot Healing និង Clone Stamp', 20),
                        ],
                    ],
                    [
                        'title_en' => 'Module 03: Typography & Khmer Poster Design',
                        'title_km' => 'ម៉ូឌុល ០៣៖ ពុម្ពអក្សរ និងផ្ទាំងផ្សព្វផ្សាយខ្មែរ',
                        'lessons' => [
                            $this->youtubeLesson('Lesson 09 — Typography Hierarchy & Khmer Font Pairing', 'មេរៀន ០៩ — ឋានានុក្រមពុម្ពអក្សរ និងការផ្គូផ្គងពុម្ពខ្មែរ', 19),
                            $this->youtubeLesson('Lesson 10 — Layer Styles (Drop Shadow, Stroke, Gradient Overlay)', 'មេរៀន ១០ — Layer Style', 17),
                            $this->youtubeLesson('Lesson 11 — Real-World Social Media Poster Design', 'មេរៀន ១១ — រចនាផ្ទាំងបណ្តាញសង្គមពិតប្រាកដ', 25),
                            $this->youtubeLesson('Lesson 12 — Exporting for High-Res Web and Print', 'មេរៀន ១២ — នាំចេញសម្រាប់គេហទំព័រ និងបោះពុម្ព', 16),
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'adobe-illustrator-beginner',
                'title_en' => 'Adobe Illustrator Beginner',
                'title_km' => 'អាដូប៊ី អ៊ីលុស្ត្រេទ័រ សម្រាប់អ្នកចាប់ផ្តើម',
                'subtitle_en' => 'Master vector graphics, logo construction, and clean illustration from scratch.',
                'subtitle_km' => 'ស្ទាត់ក្រាហ្វិកវ៉ិចទ័រ ការសាងសង់ឡូហ្គោ និងការគូររូបស្អាតពីដំបូង។',
                'mark' => 'Ai',
                'category' => 'Illustrator',
                'level' => 'Beginner',
                'price' => 35,
                'access_type' => 'paid',
                'description_en' => 'Learn vector graphics from the ground up with Adobe Illustrator CC. Master the Pen Tool, Shape Builder, Pathfinder, and clean logo construction.',
                'description_km' => 'រៀនក្រាហ្វិកវ៉ិចទ័រពីមូលដ្ឋានជាមួយ Illustrator CC៖ Pen Tool, Shape Builder, Pathfinder និងការសាងសង់ឡូហ្គោ។',
                'instructor_name' => 'Piseth Yoeurn',
                'instructor_title' => 'Senior Graphic Designer & Educator',
                'is_featured' => true,
                'outcomes' => [
                    ['en' => 'Draw precise paths with the Pen Tool', 'km' => 'គូរផ្លូវវ៉ិចទ័រឱ្យបានត្រឹមត្រូវដោយ Pen Tool'],
                    ['en' => 'Build logos with Shape Builder and Pathfinder', 'km' => 'បង្កើតឡូហ្គោដោយ Shape Builder និង Pathfinder'],
                    ['en' => 'Export SVG and print-ready vector files', 'km' => 'នាំចេញ SVG និងឯកសារវ៉ិចទ័រសម្រាប់បោះពុម្ព'],
                ],
                'modules' => [
                    [
                        'title_en' => 'Module 01: Vector Foundations',
                        'title_km' => 'ម៉ូឌុល ០១៖ មូលដ្ឋានវ៉ិចទ័រ',
                        'lessons' => [
                            $this->youtubeLesson('Lesson 01 — Illustrator Workspace & Artboards', 'មេរៀន ០១ — Workspace និង Artboard', 18),
                            $this->youtubeLesson('Lesson 02 — Pen Tool Mastery', 'មេរៀន ០២ — Pen Tool', 42),
                            $this->youtubeLesson('Lesson 03 — Shape Builder, Pathfinder & Logo Construction', 'មេរៀន ០៣ — Shape Builder, Pathfinder និងឡូហ្គោ', 50),
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'adobe-indesign-masterclass',
                'title_en' => 'Adobe InDesign Masterclass',
                'title_km' => 'អាដូប៊ី អ៊ីនឌីហ្សាញ ម៉ាស្ទ័រក្លាស',
                'subtitle_en' => 'Professional multi-page layouts, editorial grids, and automated print production.',
                'subtitle_km' => 'ប្លង់ច្រើនទំព័រជំនាញ ក្រឡាចត្រង្គបោះពុម្ព និងការផលិតបោះពុម្ពស្វ័យប្រវត្តិ។',
                'mark' => 'Id',
                'category' => 'InDesign',
                'level' => 'Intermediate',
                'price' => 45,
                'access_type' => 'paid',
                'description_en' => 'Learn how to handle multi-page layouts, master pages, paragraph and character styles, baseline grids, and automated catalogues.',
                'description_km' => 'រៀនប្លង់ច្រើនទំព័រ, master page, style អក្សរ, baseline grid និងកាតាឡុកស្វ័យប្រវត្តិ។',
                'instructor_name' => 'Piseth Yoeurn',
                'instructor_title' => 'Senior Graphic Designer & Educator',
                'is_featured' => false,
                'outcomes' => [
                    ['en' => 'Build multi-page documents with master pages', 'km' => 'បង្កើតឯកសារច្រើនទំព័រដោយ master page'],
                    ['en' => 'Use paragraph styles for bilingual book layout', 'km' => 'ប្រើ paragraph style សម្រាប់ប្លង់សៀវភៅពីរភាសា'],
                ],
                'modules' => [
                    [
                        'title_en' => 'Module 01: Editorial Layout',
                        'title_km' => 'ម៉ូឌុល ០១៖ ប្លង់បោះពុម្ព',
                        'lessons' => [
                            $this->youtubeLesson('Lesson 01 — Multi-page Layout, Grids & Khmer Typesetting', 'មេរៀន ០១ — ប្លង់ច្រើនទំព័រ និងការដាក់ពុម្ពខ្មែរ', 225),
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'graphic-design-fundamentals',
                'title_en' => 'Graphic Design Fundamentals',
                'title_km' => 'មូលដ្ឋានគ្រឹះនៃការរចនាក្រាហ្វិក',
                'subtitle_en' => 'The timeless principles every designer needs before opening any tool.',
                'subtitle_km' => 'គោលការណ៍អចិន្ត្រៃយ៍ដែលអ្នករចនាគ្រប់គ្នាត្រូវការមុនបើកឧបករណ៍ណាមួយ។',
                'mark' => 'Gd',
                'category' => 'Graphic Design',
                'level' => 'All Levels',
                'price' => 0,
                'access_type' => 'free',
                'description_en' => 'Before touching software, understand the timeless principles of graphic design. Learn how human eyes read layouts, contrast, hierarchy, and proximity.',
                'description_km' => 'មុននឹងបើកកម្មវិធី ស្វែងយល់គោលការណ៍រចនា៖ ឋានានុក្រម, កម្រិតពណ៌, និងរបៀបដែលភ្នែកអានប្លង់។',
                'instructor_name' => 'Piseth Yoeurn',
                'instructor_title' => 'Senior Graphic Designer & Educator',
                'is_featured' => true,
                'outcomes' => [
                    ['en' => 'See hierarchy, contrast, and alignment in everyday work', 'km' => 'មើលឃើញឋានានុក្រម កម្រិតពណ៌ និងការតម្រឹមក្នុងការងារប្រចាំថ្ងៃ'],
                    ['en' => 'Critique a layout before opening any design tool', 'km' => 'វាយតម្លៃប្លង់មុនបើកកម្មវិធីរចនា'],
                ],
                'modules' => [
                    [
                        'title_en' => 'Module 01: Seeing Like a Designer',
                        'title_km' => 'ម៉ូឌុល ០១៖ មើលដូចអ្នករចនា',
                        'lessons' => [
                            $this->youtubeLesson('Lesson 01 — Layout, Contrast, Hierarchy & Proximity', 'មេរៀន ០១ — ប្លង់ កម្រិតពណ៌ ឋានានុក្រម និងភាពជិត', 170),
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ui-ux-design-with-figma',
                'title_en' => 'UI/UX Design with Figma',
                'title_km' => 'ការរចនា UI/UX ជាមួយ Figma',
                'subtitle_en' => 'Design modern web and mobile apps using Figma and design systems.',
                'subtitle_km' => 'រចនាកម្មវិធីវេប និងទូរស័ព្ទទំនើបដោយប្រើ Figma និងប្រព័ន្ធរចនា។',
                'mark' => 'Fg',
                'category' => 'UI/UX',
                'level' => 'Intermediate',
                'price' => 49,
                'access_type' => 'paid',
                'description_en' => 'Design modern web and mobile apps using Figma. Master auto-layout, components, variables, design tokens, and developer handoff.',
                'description_km' => 'រចនាកម្មវិធីវេប និងទូរស័ព្ទដោយ Figma៖ auto-layout, component, variable និងការប្រគល់ឱ្យអ្នកអភិវឌ្ឍ។',
                'instructor_name' => 'Piseth Yoeurn',
                'instructor_title' => 'Senior Graphic Designer & Educator',
                'is_featured' => false,
                'outcomes' => [
                    ['en' => 'Build component libraries with auto-layout', 'km' => 'បង្កើតបណ្ណាល័យ component ដោយ auto-layout'],
                    ['en' => 'Hand files to developers with clean tokens', 'km' => 'ប្រគល់ឯកសារឱ្យអ្នកអភិវឌ្ឍដោយ token ច្បាស់'],
                ],
                'modules' => [
                    [
                        'title_en' => 'Module 01: Figma for Product UI',
                        'title_km' => 'ម៉ូឌុល ០១៖ Figma សម្រាប់ UI ផលិតផល',
                        'lessons' => [
                            $this->youtubeLesson('Lesson 01 — Auto-layout, Components, Variables & Handoff', 'មេរៀន ០១ — Auto-layout, Component, Variable និងការប្រគល់', 255),
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'motion-graphics-with-after-effects',
                'title_en' => 'Motion Graphics with After Effects',
                'title_km' => 'Motion Graphics ជាមួយ After Effects',
                'subtitle_en' => 'Transform Photoshop and Illustrator files into dynamic animated videos.',
                'subtitle_km' => 'បំប្លែងឯកសារ Photoshop និង Illustrator ទៅជាវីដេអូចលនាដ៏រស់រវើក។',
                'mark' => 'Ae',
                'category' => 'Motion Graphics',
                'level' => 'Intermediate',
                'price' => 55,
                'access_type' => 'paid',
                'description_en' => 'Transform Photoshop and Illustrator files into dynamic animated videos. Learn easing curves, kinetic typography, and social media video reels.',
                'description_km' => 'បំប្លែងឯកសារ Photoshop និង Illustrator ទៅជាវីដេអូ៖ easing, kinetic type និង reel បណ្តាញសង្គម។',
                'instructor_name' => 'Piseth Yoeurn',
                'instructor_title' => 'Senior Graphic Designer & Educator',
                'is_featured' => false,
                'outcomes' => [
                    ['en' => 'Animate type and shape layers with easing', 'km' => 'ធ្វើចលនាអក្សរ និងរូបរាងដោយ easing'],
                    ['en' => 'Export social reels from After Effects', 'km' => 'នាំចេញ reel បណ្តាញសង្គមពី After Effects'],
                ],
                'modules' => [
                    [
                        'title_en' => 'Module 01: Motion from Still Files',
                        'title_km' => 'ម៉ូឌុល ០១៖ ធ្វើចលនាពីឯកសារឋិតិវន្ត',
                        'lessons' => [
                            $this->youtubeLesson('Lesson 01 — Easing, Kinetic Type & Social Reels', 'មេរៀន ០១ — Easing, Kinetic Type និង Reel', 285),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function youtubeLesson(string $titleEn, string $titleKm, int $minutes, ?string $notesEn = null, ?string $notesKm = null): array
    {
        return [
            'title_en' => $titleEn,
            'title_km' => $titleKm,
            'video_host' => 'youtube',
            'video_url' => self::DEMO_YOUTUBE,
            'duration_minutes' => $minutes,
            'notes_en' => $notesEn ?? 'Watch the demonstration, then pause and try the same steps on your own file.',
            'notes_km' => $notesKm ?? 'មើលការបង្ហាញ រួចផ្អាក សាកល្បងជំហានដូចគ្នាលើឯកសារផ្ទាល់ខ្លួន។',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function driveLesson(string $titleEn, string $titleKm, int $minutes): array
    {
        return [
            'title_en' => $titleEn,
            'title_km' => $titleKm,
            'video_host' => 'drive',
            'video_url' => null,
            'duration_minutes' => $minutes,
            'notes_en' => 'This lesson is hosted on Google Drive. Paste a share link when the admin video manager lands.',
            'notes_km' => 'មេរៀននេះស្ថិតនៅលើ Google Drive។ នឹងបញ្ចូលតំណភ្ជាប់នៅពេលមានផ្ទាំងគ្រប់គ្រងវីដេអូ។',
        ];
    }
}
