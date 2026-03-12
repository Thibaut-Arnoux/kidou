<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Category;
use Illuminate\Database\Seeder;

final class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $achievements = [
            'motor-skills' => [
                ['name' => 'Head Control', 'description' => 'Holds head steady and upright when held in a sitting position.', 'expected_age_min_months' => 1, 'expected_age_max_months' => 4],
                ['name' => 'Pushes Up on Arms', 'description' => 'Lifts head and chest off the floor while lying on tummy.', 'expected_age_min_months' => 2, 'expected_age_max_months' => 4],
                ['name' => 'Rolls Over', 'description' => 'Rolls from tummy to back and back to tummy.', 'expected_age_min_months' => 3, 'expected_age_max_months' => 6],
                ['name' => 'Sits Without Support', 'description' => 'Sits steadily on their own without needing to prop on hands.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 8],
                ['name' => 'Raking Grasp', 'description' => 'Uses fingers to rake small objects toward themselves.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 8],
                ['name' => 'Crawls', 'description' => 'Moves forward on hands and knees in a coordinated way.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 10],
                ['name' => 'Pulls to Stand', 'description' => 'Pulls themselves up to a standing position using furniture.', 'expected_age_min_months' => 7, 'expected_age_max_months' => 12],
                ['name' => 'Pincer Grasp', 'description' => 'Picks up small objects between thumb and forefinger.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 12],
                ['name' => 'Cruises Along Furniture', 'description' => 'Walks sideways while holding onto furniture for support.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 13],
                ['name' => 'First Steps Independently', 'description' => 'Takes several steps without holding onto anything.', 'expected_age_min_months' => 9, 'expected_age_max_months' => 15],
                ['name' => 'Stacks Two Blocks', 'description' => 'Places one block on top of another deliberately.', 'expected_age_min_months' => 11, 'expected_age_max_months' => 16],
                ['name' => 'Walks Steadily', 'description' => 'Walks with a stable, coordinated gait and rarely falls.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 18],
                ['name' => 'Kicks a Ball', 'description' => 'Kicks a ball forward with one foot while standing.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 24],
                ['name' => 'Runs', 'description' => 'Runs with a fairly coordinated gait.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Climbs Stairs with Help', 'description' => "Walks up stairs while holding a railing or an adult's hand.", 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Jumps with Both Feet', 'description' => 'Jumps off the ground with both feet leaving the floor.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
                ['name' => 'Pedals a Tricycle', 'description' => 'Pushes pedals on a tricycle to move forward.', 'expected_age_min_months' => 28, 'expected_age_max_months' => 36],
            ],
            'language' => [
                ['name' => 'Cooing', 'description' => 'Produces soft vowel-like sounds in response to voices.', 'expected_age_min_months' => 1, 'expected_age_max_months' => 4],
                ['name' => 'Babbling', 'description' => 'Repeats consonant-vowel combinations like "bababa" or "mamama".', 'expected_age_min_months' => 4, 'expected_age_max_months' => 8],
                ['name' => 'Responds to Own Name', 'description' => 'Turns head or looks toward the speaker when their name is called.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 9],
                ['name' => 'First Word', 'description' => 'Says one or two recognizable words with meaning.', 'expected_age_min_months' => 9, 'expected_age_max_months' => 14],
                ['name' => 'Points to Show or Request', 'description' => 'Points at objects to express interest or ask for something.', 'expected_age_min_months' => 9, 'expected_age_max_months' => 14],
                ['name' => 'Follows Simple Instructions', 'description' => 'Understands and carries out a one-step request.', 'expected_age_min_months' => 10, 'expected_age_max_months' => 16],
                ['name' => 'Uses 10+ Words', 'description' => 'Has a spoken vocabulary of at least ten distinct words.', 'expected_age_min_months' => 14, 'expected_age_max_months' => 20],
                ['name' => 'Two-Word Phrases', 'description' => 'Combines two words to form simple phrases like "more milk".', 'expected_age_min_months' => 18, 'expected_age_max_months' => 24],
                ['name' => 'Names Familiar Objects', 'description' => 'Can label common objects when asked "what\'s this?"', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Uses Short Sentences', 'description' => 'Speaks in sentences of three or more words.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
                ['name' => 'Strangers Can Understand Speech', 'description' => 'Most of what the child says can be understood by unfamiliar adults.', 'expected_age_min_months' => 30, 'expected_age_max_months' => 36],
            ],
            'social-emotional' => [
                ['name' => 'Social Smile', 'description' => "Smiles in response to a caregiver's face or voice.", 'expected_age_min_months' => 1, 'expected_age_max_months' => 3],
                ['name' => 'Enjoys Social Play', 'description' => 'Laughs during peek-a-boo and other simple interactive games.', 'expected_age_min_months' => 3, 'expected_age_max_months' => 6],
                ['name' => 'Stranger Anxiety', 'description' => 'Shows wariness or distress around unfamiliar people.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 10],
                ['name' => 'Separation Anxiety', 'description' => 'Becomes upset when a primary caregiver leaves the room.', 'expected_age_min_months' => 7, 'expected_age_max_months' => 12],
                ['name' => 'Waves Bye-Bye', 'description' => 'Waves hand in a social gesture when someone leaves.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 14],
                ['name' => 'Shows Affection', 'description' => 'Hugs, kisses, or cuddles with familiar people spontaneously.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 18],
                ['name' => 'Parallel Play', 'description' => 'Plays alongside other children doing similar activities.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Shows Empathy', 'description' => 'Notices when another person is upset and may try to comfort them.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 28],
                ['name' => 'Takes Turns in Simple Games', 'description' => 'Can wait briefly and alternate turns during a simple game.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
                ['name' => 'Engages in Pretend Play with Others', 'description' => 'Participates in make-believe scenarios with peers or caregivers.', 'expected_age_min_months' => 28, 'expected_age_max_months' => 36],
            ],
            'cognitive' => [
                ['name' => 'Follows Moving Objects', 'description' => 'Tracks a slowly moving object with their eyes.', 'expected_age_min_months' => 0, 'expected_age_max_months' => 3],
                ['name' => 'Explores Objects with Hands and Mouth', 'description' => 'Brings objects to mouth and manipulates them.', 'expected_age_min_months' => 3, 'expected_age_max_months' => 6],
                ['name' => 'Object Permanence', 'description' => 'Understands that an object still exists even when hidden.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 10],
                ['name' => 'Cause and Effect', 'description' => 'Intentionally repeats actions to see results.', 'expected_age_min_months' => 6, 'expected_age_max_months' => 12],
                ['name' => 'Imitates Actions', 'description' => 'Copies simple gestures and actions performed by adults.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 14],
                ['name' => 'Simple Shape Sorting', 'description' => 'Fits basic shapes into the correct holes on a shape sorter.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 26],
                ['name' => 'Pretend Play', 'description' => 'Uses objects symbolically, such as pretending a block is a phone.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 28],
                ['name' => 'Sorts by Color or Shape', 'description' => 'Groups objects together based on one attribute.', 'expected_age_min_months' => 28, 'expected_age_max_months' => 36],
            ],
            'self-care' => [
                ['name' => 'Holds Own Bottle', 'description' => 'Grasps and holds a bottle to feed independently.', 'expected_age_min_months' => 5, 'expected_age_max_months' => 9],
                ['name' => 'Finger Feeds', 'description' => 'Picks up small pieces of food and brings them to mouth.', 'expected_age_min_months' => 7, 'expected_age_max_months' => 10],
                ['name' => 'Drinks from Cup with Help', 'description' => 'Takes sips from a cup held or guided by a caregiver.', 'expected_age_min_months' => 8, 'expected_age_max_months' => 14],
                ['name' => 'Uses a Spoon (with spilling)', 'description' => 'Attempts to scoop food with a spoon, though messily.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 18],
                ['name' => 'Removes Simple Clothing', 'description' => 'Pulls off loose items like socks, shoes, or a hat.', 'expected_age_min_months' => 12, 'expected_age_max_months' => 20],
                ['name' => 'Drinks from Cup Independently', 'description' => 'Holds and drinks from a cup without significant spilling.', 'expected_age_min_months' => 16, 'expected_age_max_months' => 24],
                ['name' => 'Uses a Spoon Neatly', 'description' => 'Feeds themselves with a spoon with minimal spilling.', 'expected_age_min_months' => 20, 'expected_age_max_months' => 28],
                ['name' => 'Shows Interest in Toilet Training', 'description' => 'Tells caregiver about wet or dirty diaper, or wants to sit on the potty.', 'expected_age_min_months' => 18, 'expected_age_max_months' => 30],
                ['name' => 'Helps with Dressing', 'description' => 'Cooperates by pushing arms through sleeves or stepping into pants.', 'expected_age_min_months' => 20, 'expected_age_max_months' => 30],
                ['name' => 'Washes and Dries Hands with Help', 'description' => 'Participates in hand washing with guidance.', 'expected_age_min_months' => 24, 'expected_age_max_months' => 36],
            ],
        ];

        foreach ($achievements as $categorySlug => $categoryAchievements) {
            $category = Category::query()->where('slug', $categorySlug)->firstOrFail();

            foreach ($categoryAchievements as $achievement) {
                Achievement::query()->firstOrCreate(
                    ['name' => $achievement['name'], 'category_id' => $category->id],
                    [...$achievement, 'user_id' => null],
                );
            }
        }
    }
}
