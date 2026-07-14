-- KAAGAZZ Book Store Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS kaagazz_db;
USE kaagazz_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'seller', 'admin') DEFAULT 'user',
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT,
    seller_id INT,
    image VARCHAR(255) DEFAULT 'default-book.png',
    stock INT DEFAULT 1,
    isbn VARCHAR(20) DEFAULT NULL,
    published_year YEAR DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    payment_method VARCHAR(50) DEFAULT 'cod',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, book_id)
);

-- Wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, book_id)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, slug) VALUES
('Fiction', 'fiction'),
('Non-Fiction', 'non-fiction'),
('Science', 'science'),
('Technology', 'technology'),
('Business', 'business'),
('Biography', 'biography'),
('Children', 'children'),
('Textbooks', 'textbooks');

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@kaagazz.com', '$2y$10$8K1p/a0dR1xqM8K3hQv1aOQZQZQZQZQZQZQZQZQZQZQZQZQZQZQZQ', 'admin');

-- Insert sample books - Fiction (Category 1)
INSERT INTO books (title, author, description, price, category_id, seller_id, image, stock) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'A classic American novel set in the Jazz Age.', 12.99, 1, 1, 'book-1.png', 50),
('To Kill a Mockingbird', 'Harper Lee', 'A gripping tale of racial injustice.', 14.99, 1, 1, 'book-2.png', 35),
('1984', 'George Orwell', 'A dystopian social science fiction novel.', 11.99, 1, 1, 'book-3.png', 40),
('Pride and Prejudice', 'Jane Austen', 'A romantic novel of manners.', 10.99, 1, 1, 'book-4.png', 45),
('The Catcher in the Rye', 'J.D. Salinger', 'A story about teenage angst and alienation.', 13.99, 1, 1, 'book-5.png', 38),
('Brave New World', 'Aldous Huxley', 'A dystopian social science fiction novel.', 12.99, 1, 1, 'book-6.png', 42),
('The Hobbit', 'J.R.R. Tolkien', 'A fantasy novel about the adventures of hobbit Bilbo Baggins.', 15.99, 1, 1, 'book-7.png', 55),
('Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', 'The first book in the Harry Potter series.', 16.99, 1, 1, 'book-8.png', 60),
('The Lord of the Rings', 'J.R.R. Tolkien', 'An epic high-fantasy novel.', 24.99, 1, 1, 'book-9.png', 30),
('The Alchemist', 'Paulo Coelho', 'A novel about a young Andalusian shepherd.', 14.99, 1, 1, 'book-10.png', 48),
('The Da Vinci Code', 'Dan Brown', 'A mystery thriller novel.', 15.99, 1, 1, 'book3.png', 40),
('The Hunger Games', 'Suzanne Collins', 'A dystopian novel about a deadly competition.', 13.99, 1, 1, 'book5.png', 50),
('Dune', 'Frank Herbert', 'A science fiction masterpiece about politics and religion.', 18.99, 1, 1, 'book7.png', 35),

-- Non-Fiction (Category 2)
('Sapiens', 'Yuval Noah Harari', 'A brief history of humankind.', 18.99, 2, 1, 'book-4.png', 25),
('Educated', 'Tara Westover', 'A memoir about a young woman who leaves her survivalist family.', 17.99, 2, 1, 'book-5.png', 30),
('Becoming', 'Michelle Obama', 'The memoir of former First Lady Michelle Obama.', 19.99, 2, 1, 'book-6.png', 40),
('Thinking, Fast and Slow', 'Daniel Kahneman', 'A book about the psychology of human decision-making.', 16.99, 2, 1, 'book-7.png', 28),
('The Power of Habit', 'Charles Duhigg', 'Why we do what we do in life and business.', 15.99, 2, 1, 'book-8.png', 35),
('Atomic Habits', 'James Clear', 'An easy way to build good habits.', 16.99, 2, 1, 'book-9.png', 50),
('Outliers', 'Malcolm Gladwell', 'The story of success and what makes high-achievers different.', 15.99, 2, 1, 'book-10.png', 32),
('The Subtle Art of Not Giving a F*ck', 'Mark Manson', 'A counterintuitive approach to living a good life.', 14.99, 2, 1, 'book3.png', 45),
('Quiet', 'Susan Cain', 'The power of introverts in a world that can\'t stop talking.', 15.99, 2, 1, 'book5.png', 38),
('Born a Crime', 'Trevor Noah', 'Stories from a South African childhood.', 16.99, 2, 1, 'book7.png', 42),

-- Science (Category 3)
('A Brief History of Time', 'Stephen Hawking', 'A book on cosmology for the layperson.', 13.99, 3, 1, 'book-1.png', 30),
('The Selfish Gene', 'Richard Dawkins', 'A book on evolution and genetics.', 15.99, 3, 1, 'book-2.png', 25),
('Cosmos', 'Carl Sagan', 'A popular science book about the universe.', 16.99, 3, 1, 'book-3.png', 35),
('The Gene', 'Siddhartha Mukherjee', 'An intimate history of genetics.', 18.99, 3, 1, 'book-4.png', 28),
('Silent Spring', 'Rachel Carson', 'A book about environmental science and pesticide effects.', 14.99, 3, 1, 'book-5.png', 40),
('The Immortal Life of Henrietta Lacks', 'Rebecca Skloot', 'A book about medical ethics and scientific discovery.', 15.99, 3, 1, 'book-6.png', 33),
('Surely You\'re Joking, Mr. Feynman!', 'Richard Feynman', 'Adventures of a curious character.', 14.99, 3, 1, 'book-7.png', 38),
('The Double Helix', 'James Watson', 'A personal account of the discovery of DNA.', 13.99, 3, 1, 'book-8.png', 45),
('What If?', 'Randall Munroe', 'Serious scientific answers to absurd hypothetical questions.', 16.99, 3, 1, 'book-9.png', 50),
('Sapiens', 'Yuval Noah Harari', 'A brief history of humankind.', 18.99, 3, 1, 'book-10.png', 25),

-- Technology (Category 4)
('The Innovators', 'Walter Isaacson', 'How a group of hackers, geniuses, and geeks created the digital revolution.', 21.99, 4, 1, 'book-5.png', 15),
('Clean Code', 'Robert C. Martin', 'A Handbook of Agile Software Craftsmanship.', 34.99, 4, 1, 'book-1.png', 20),
('The Pragmatic Programmer', 'Andrew Hunt', 'Your journey to mastery in programming.', 42.99, 4, 1, 'book-2.png', 18),
('Introduction to Algorithms', 'Thomas Cormen', 'The comprehensive guide to algorithms.', 89.99, 4, 1, 'book-3.png', 12),
('Code Complete', 'Steve McConnell', 'A practical handbook of software construction.', 49.99, 4, 1, 'book-4.png', 15),
('The Mythical Man-Month', 'Frederick Brooks', 'Essays on software engineering.', 39.99, 4, 1, 'book-6.png', 22),
('Design Patterns', 'Gang of Four', 'Elements of reusable object-oriented software.', 54.99, 4, 1, 'book-7.png', 10),
('You Don\'t Know JS', 'Kyle Simpson', 'A deep dive into JavaScript.', 29.99, 4, 1, 'book-8.png', 30),
('Python Crash Course', 'Eric Matthes', 'A hands-on project-based introduction to programming.', 24.99, 4, 1, 'book-9.png', 40),
('The Clean Coder', 'Robert C. Martin', 'A code of conduct for professional programmers.', 32.99, 4, 1, 'book-10.png', 25),
('JavaScript: The Good Parts', 'Douglas Crockford', 'Unearthing the excellence in JavaScript.', 27.99, 4, 1, 'book3.png', 35),

-- Business (Category 5)
('Zero to One', 'Peter Thiel', 'Notes on startups, or how to build the future.', 17.99, 5, 1, 'book-2.png', 35),
('The Lean Startup', 'Eric Ries', 'How today\'s entrepreneurs use continuous innovation.', 16.99, 5, 1, 'book-3.png', 40),
('Good to Great', 'Jim Collins', 'Why some companies make the leap and others don\'t.', 19.99, 5, 1, 'book-4.png', 28),
('Rich Dad Poor Dad', 'Robert Kiyosaki', 'What the rich teach their kids about money.', 12.99, 5, 1, 'book-5.png', 50),
('The 7 Habits of Highly Effective People', 'Stephen Covey', 'Powerful lessons in personal change.', 17.99, 5, 1, 'book-6.png', 45),
('Think and Grow Rich', 'Napoleon Hill', 'The classic book on success and wealth.', 14.99, 5, 1, 'book-7.png', 38),
('How to Win Friends and Influence People', 'Dale Carnegie', 'The classic guide to interpersonal skills.', 13.99, 5, 1, 'book-8.png', 55),
('The $100 Startup', 'Chris Guillebeau', 'Reinvent the way you make a living.', 15.99, 5, 1, 'book-9.png', 42),
('Start with Why', 'Simon Sinek', 'How great leaders inspire everyone to take action.', 16.99, 5, 1, 'book-10.png', 48),
('Shoe Dog', 'Phil Knight', 'A memoir by the creator of Nike.', 18.99, 5, 1, 'book3.png', 30),
('The Hard Thing About Hard Things', 'Ben Horowitz', 'Building a business when there are no easy answers.', 19.99, 5, 1, 'book5.png', 25),

-- Biography (Category 6)
('Steve Jobs', 'Walter Isaacson', 'The exclusive biography of Steve Jobs.', 19.99, 6, 1, 'book-4.png', 40),
('Einstein', 'Walter Isaacson', 'His life and universe.', 18.99, 6, 1, 'book-5.png', 32),
('Benjamin Franklin', 'Walter Isaacson', 'An American Life.', 17.99, 6, 1, 'book-6.png', 28),
('Long Walk to Freedom', 'Nelson Mandela', 'The autobiography of Nelson Mandela.', 16.99, 6, 1, 'book-7.png', 35),
('The Diary of a Young Girl', 'Anne Frank', 'The diary of Anne Frank.', 11.99, 6, 1, 'book-8.png', 50),
('Alexander Hamilton', 'Ron Chernow', 'The biography that inspired the musical.', 18.99, 6, 1, 'book-9.png', 30),
('Team of Rivals', 'Doris Kearns Goodwin', 'The political genius of Abraham Lincoln.', 19.99, 6, 1, 'book-10.png', 25),
('Wings of Fire', 'A.P.J. Abdul Kalam', 'An autobiography of India\'s missile man.', 14.99, 6, 1, 'book3.png', 45),
('Mahatma Gandhi', 'Rajmohan Gandhi', 'The biography of the father of the nation.', 16.99, 6, 1, 'book5.png', 38),
('Frida', 'Hayden Herrera', 'A biography of Frida Kahlo.', 18.99, 6, 1, 'book7.png', 28),

-- Children (Category 7)
('The Very Hungry Caterpillar', 'Eric Carle', 'A classic children\'s picture book.', 8.99, 7, 1, 'book-1.png', 60),
('Where the Wild Things Are', 'Maurice Sendak', 'A beloved children\'s picture book.', 9.99, 7, 1, 'book-2.png', 55),
('Charlotte\'s Web', 'E.B. White', 'A classic of children\'s literature.', 10.99, 7, 1, 'book-3.png', 50),
('The Gruffalo', 'Julia Donaldson', 'A rhyming story for young readers.', 8.99, 7, 1, 'book-4.png', 65),
('Matilda', 'Roald Dahl', 'A story about a brilliant young girl.', 11.99, 7, 1, 'book-5.png', 48),
('The Chronicles of Narnia', 'C.S. Lewis', 'A series of seven fantasy novels.', 24.99, 7, 1, 'book-6.png', 35),
('Percy Jackson & the Olympians', 'Rick Riordan', 'A pentalogy of adventure novels.', 12.99, 7, 1, 'book-7.png', 45),
('Diary of a Wimpy Kid', 'Jeff Kinney', 'A children\'s novel with illustrations.', 10.99, 7, 1, 'book-8.png', 58),
('Wonder', 'R.J. Palacio', 'A story about kindness and acceptance.', 11.99, 7, 1, 'book-9.png', 52),
('The Tale of Peter Rabbit', 'Beatrix Potter', 'A classic children\'s story.', 7.99, 7, 1, 'book-10.png', 70),

-- Textbooks (Category 8)
('Calculus: Early Transcendentals', 'James Stewart', 'A comprehensive calculus textbook.', 89.99, 8, 1, 'book-1.png', 20),
('Campbell Biology', 'Lisa Urry', 'The leading biology textbook.', 119.99, 8, 1, 'book-2.png', 15),
('Organic Chemistry', 'Paula Bruice', 'A comprehensive organic chemistry text.', 109.99, 8, 1, 'book-3.png', 18),
('Principles of Economics', 'Gregory Mankiw', 'A comprehensive economics textbook.', 99.99, 8, 1, 'book-4.png', 22),
('Psychology', 'David Myers', 'An introductory psychology textbook.', 89.99, 8, 1, 'book-5.png', 25),
('Physics for Scientists and Engineers', 'Serway', 'A comprehensive physics textbook.', 99.99, 8, 1, 'book-6.png', 20),
('Chemistry: The Central Science', 'Brown', 'The leading general chemistry textbook.', 109.99, 8, 1, 'book-7.png', 18),
('Human Anatomy & Physiology', 'Marieb', 'A comprehensive A&P textbook.', 114.99, 8, 1, 'book-8.png', 16),
('Sociology', 'John Macionis', 'An introductory sociology textbook.', 79.99, 8, 1, 'book-9.png', 24),
('Statistics', 'David Freedman', 'A comprehensive statistics textbook.', 84.99, 8, 1, 'book-10.png', 20);
