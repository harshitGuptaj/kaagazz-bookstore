<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

echo "=== Updating Book Cover Images ===\n\n";

// Mapping: book_id => real image filename
$images = [
    // Fiction (1-13)
    1 => 'book-1-the-great-gatsby.jpg',
    2 => 'book-2-to-kill-a-mockingbird.jpg',
    3 => 'book-3-1984.jpg',
    4 => 'book-6-pride-and-prejudice.jpg',
    5 => 'book-7-the-catcher-in-the-rye.jpg',
    6 => 'book-1.png',
    7 => 'book-10-the-hobbit.jpg',
    8 => 'book-8-harry-potter-and-the-philosopher-s-stone.jpg',
    9 => 'book-9-the-lord-of-the-rings.jpg',
    10 => 'book-12-the-alchemist.jpg',
    11 => 'book-2.png',
    12 => 'book-3.png',
    13 => 'book-4.png',

    // Non-Fiction (14-23)
    14 => 'book-4-sapiens.jpg',
    15 => 'book-17-educated.jpg',
    16 => 'book-18-becoming.jpg',
    17 => 'book-21-thinking--fast-and-slow.jpg',
    18 => 'book-22-the-power-of-habit.jpg',
    19 => 'book-23-atomic-habits.jpg',
    20 => 'book-24-outliers.jpg',
    21 => 'book-5.png',
    22 => 'book-20-quiet--the-power-of-introverts.jpg',
    23 => 'book-6.png',

    // Science (24-33)
    24 => 'book-26-a-brief-history-of-time.jpg',
    25 => 'book-27-the-selfish-gene.jpg',
    26 => 'book-28-cosmos.jpg',
    27 => 'book-30-the-gene.jpg',
    28 => 'book-33-silent-spring.jpg',
    29 => 'book-19-the-immortal-life-of-henrietta-lacks.jpg',
    30 => 'book-7.png',
    31 => 'book-8.png',
    32 => 'book-9.png',
    33 => 'book-31-astrophysics-for-people-in-a-hurry.jpg',

    // Technology (34-44)
    34 => 'book-5-the-innovators.jpg',
    35 => 'book-36-clean-code.jpg',
    36 => 'book-37-the-pragmatic-programmer.jpg',
    37 => 'book-38-introduction-to-algorithms.jpg',
    38 => 'book-40-code-complete.jpg',
    39 => 'book-44-the-mythical-man-month.jpg',
    40 => 'book-39-design-patterns.jpg',
    41 => 'book-1.png',
    42 => 'book-2.png',
    43 => 'book-41-the-clean-coder.jpg',
    44 => 'book-3.png',

    // Business (45-55)
    45 => 'book-46-zero-to-one.jpg',
    46 => 'book-47-the-lean-startup.jpg',
    47 => 'book-48-good-to-great.jpg',
    48 => 'book-49-rich-dad-poor-dad.jpg',
    49 => 'book-4.png',
    50 => 'book-51-think-and-grow-rich.jpg',
    51 => 'book-5.png',
    52 => 'book-53-the--100-startup.jpg',
    53 => 'book-55-start-with-why.jpg',
    54 => 'book-52-shoe-dog.jpg',
    55 => 'book-6.png',

    // Biography (56-65)
    56 => 'book-56-steve-jobs.jpg',
    57 => 'book-57-einstein.jpg',
    58 => 'book-61-benjamin-franklin.jpg',
    59 => 'book-60-long-walk-to-freedom.jpg',
    60 => 'book-59-the-diary-of-a-young-girl.jpg',
    61 => 'book-62-alexander-hamilton.jpg',
    62 => 'book-65-team-of-rivals.jpg',
    63 => 'book-7.png',
    64 => 'book-8.png',
    65 => 'book-9.png',

    // Children (66-75)
    66 => 'book-67-the-very-hungry-caterpillar.jpg',
    67 => 'book-68-where-the-wild-things-are.jpg',
    68 => 'book-66-charlotte-s-web.jpg',
    69 => 'book-70-the-gruffalo.jpg',
    70 => 'book-69-matilda.jpg',
    71 => 'book-10.png',
    72 => 'book-74-percy-jackson.jpg',
    73 => 'book-75-diary-of-a-wimpy-kid.jpg',
    74 => 'book-1.png',
    75 => 'book-2.png',

    // Textbooks (76-85)
    76 => 'book-76-calculus.jpg',
    77 => 'book-77-campbell-biology.jpg',
    78 => 'book-78-organic-chemistry.jpg',
    79 => 'book-82-economics.jpg',
    80 => 'book-81-psychology.jpg',
    81 => 'book-79-physics-for-scientists-and-engineers.jpg',
    82 => 'book-80-chemistry.jpg',
    83 => 'book-3.png',
    84 => 'book-4.png',
    85 => 'book-5.png',
];

$updated = 0;
foreach ($images as $id => $image) {
    $stmt = $conn->prepare("UPDATE books SET image = ? WHERE id = ?");
    $stmt->bind_param("si", $image, $id);
    if ($stmt->execute()) {
        $updated++;
    }
    $stmt->close();
}

echo "Updated $updated books with real cover images.\n";

// Verify
echo "\n=== Sample books with images ===\n";
$result = $conn->query("SELECT id, title, image FROM books ORDER BY id LIMIT 20");
while ($row = $result->fetch_assoc()) {
    echo "  {$row['id']}: {$row['title']} => {$row['image']}\n";
}
