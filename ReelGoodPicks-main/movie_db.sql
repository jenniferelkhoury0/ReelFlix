-- Create database and select it
CREATE DATABASE IF NOT EXISTS movies_db;
USE movies_db;

CREATE TABLE ACTORS (
    ID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    NAME VARCHAR(255),
    GENDER VARCHAR(10),
    DATE_OF_BIRTH DATE,
    NATIONALITY VARCHAR(50),
    BIO TEXT,
    IMAGEURL VARCHAR(255)
);

CREATE TABLE MOVIES (
    ID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    TITLE VARCHAR(255),
    RELEASE_YEAR INT,
    DIRECTOR VARCHAR(100),
    POSTERURL VARCHAR(255),
    DESCRIPTION TEXT,
    TRAILERURL VARCHAR(255),
    DURATION INT,
    LANGUAGE VARCHAR(50),
    COUNTRY VARCHAR(50),
    BOX_OFFICE DECIMAL(15,2) DEFAULT 0,
    RATING DECIMAL(3,1),
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE MOVIE_ACTORS (
    MOVIE_ID INT,
    ACTOR_ID INT,
    ROLE VARCHAR(100),
    PRIMARY KEY (MOVIE_ID, ACTOR_ID),
    FOREIGN KEY (MOVIE_ID) REFERENCES MOVIES(ID),
    FOREIGN KEY (ACTOR_ID) REFERENCES ACTORS(ID)
);

CREATE TABLE MOVIE_GENRES (
    MOVIE_ID INT,
    GENRE VARCHAR(50),
    PRIMARY KEY (MOVIE_ID, GENRE),
    FOREIGN KEY (MOVIE_ID) REFERENCES MOVIES(ID)
);

CREATE TABLE USERS (
    ID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    USERNAME VARCHAR(50) UNIQUE,
    HASHEDPASSWORD VARCHAR(255),
    FULLNAME VARCHAR(255),
    EMAIL VARCHAR(100) UNIQUE,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    DOB DATE,
    BIO TEXT,
    PROFILE_PIC_URL VARCHAR(255)
);

CREATE TABLE REVIEWS (
    ID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    MOVIE_ID INT,
    USER_ID INT,
    RATING INT CHECK (RATING >= 1 AND RATING <= 10),
    REVIEW_TEXT TEXT,
    POSTED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (MOVIE_ID) REFERENCES MOVIES(ID),
    FOREIGN KEY (USER_ID) REFERENCES USERS(ID)
);

CREATE TABLE WATCHLIST (
    USER_ID INT,
    MOVIE_ID INT,
    ADDED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (USER_ID, MOVIE_ID),
    FOREIGN KEY (USER_ID) REFERENCES USERS(ID),
    FOREIGN KEY (MOVIE_ID) REFERENCES MOVIES(ID)
);

CREATE TABLE USER_FAVORITES (
    USER_ID INT,
    MOVIE_ID INT,
    ADDED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (USER_ID, MOVIE_ID),
    FOREIGN KEY (USER_ID) REFERENCES USERS(ID),
    FOREIGN KEY (MOVIE_ID) REFERENCES MOVIES(ID)
);

CREATE TABLE IF NOT EXISTS FEEDBACK (
    ID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    NAME VARCHAR(255) NOT NULL,
    EMAIL VARCHAR(100),
    FEEDBACK_TYPE VARCHAR(50),
    RATING INT DEFAULT NULL,
    MESSAGE TEXT NOT NULL,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Removed ALTER TABLE because ID columns now include AUTO_INCREMENT in their CREATE statements.

-- SQL Insert statements for movie database dummy data
INSERT INTO movies (TITLE, RELEASE_YEAR, DIRECTOR, POSTERURL, DESCRIPTION, TRAILERURL, DURATION, LANGUAGE, COUNTRY, BOX_OFFICE, RATING) VALUES
('Mad Max: Fury Road', 2015, 'George Miller', 'https://fr.web.img3.acsta.net/pictures/15/04/14/18/30/215297.jpg', 'A high-octane action thriller set in a post-apocalyptic wasteland, where Max teams up with Furiosa to escape a tyrannical leader.', 'https://www.youtube.com/watch?v=hEJnMQG9ev8', 120, 'English', 'Australia', 375000000, 8.1),
('Avengers: Endgame', 2019, 'Anthony and Joe Russo', 'https://th.bing.com/th/id/OIP.AsVeA2uTH8DIHIezO_yDGAHaK-?rs=1&pid=ImgDetMain', 'The epic conclusion to the Avengers saga, where the heroes assemble to reverse the damage caused by Thanos and restore the universe.', 'https://www.youtube.com/watch?v=TcMBFSGVi1c', 181, 'English', 'USA', 2797800000, 8.4),
('Inception', 2010, 'Christopher Nolan', 'https://th.bing.com/th/id/OIP.vnJImFIy1GEoBBAjyZ-tfQHaK-?rs=1&pid=ImgDetMain', 'A mind-bending sci-fi thriller about a thief who enters the dreams of others to steal secrets, but is tasked with planting an idea instead.', 'https://www.youtube.com/watch?v=YoHD9XEInc0', 148, 'English', 'USA', 830000000, 8.8),
('The Matrix', 1999, 'The Wachowskis', 'https://th.bing.com/th/id/OIP.L-BlLPvjY6VUlKwWOa-c4gHaLH?rs=1&pid=ImgDetMain', 'A groundbreaking sci-fi film about a hacker who discovers the reality of the simulated world and joins the rebellion against machines.', 'https://www.youtube.com/watch?v=vKQi3bBA1y8', 136, 'English', 'USA', 465000000, 8.7),
('الجزيرة', 2007, 'Sherif Arafa', 'https://th.bing.com/th/id/OIP.67_cnNgDjkn3aIve76nXqAAAAA?rs=1&pid=ImgDetMain', 'A gripping action-drama set in the Middle East, exploring themes of survival and resilience in the face of adversity.', 'https://www.youtube.com/watch?v=9nJ9ALOrFfY', 120, 'Arabic', 'Egypt', 30000000, 7.3),
('John Wick', 2014, 'Chad Stahelski', 'https://th.bing.com/th/id/OIP.vBwYjW3dg7cMGtP9Ejm5awHaLH?rs=1&pid=ImgDetMain', 'A retired hitman seeks vengeance after the death of his dog, leading to a brutal and stylish action-packed journey.', 'https://www.youtube.com/watch?v=2AUmvWm5ZD0', 101, 'English', 'USA', 86000000, 7.4),
('Die Hard', 1988, 'John McTiernan', 'https://th.bing.com/th/id/OIP.-gEQOJxDh_WKHAyLoTYvkQHaK-?w=131&h=195&c=7&r=0&o=5&dpr=2&pid=1.7', 'A New York cop battles terrorists during a Christmas party in a Los Angeles skyscraper.', 'https://www.youtube.com/watch?v=QmYQylmGBvQ', 132, 'English', 'USA', 140000000, 8.2),
('Mission: Impossible - Fallout', 2018, 'Christopher McQuarrie', 'https://th.bing.com/th/id/OIP.tE_FdGciO1pgppC6wHroygHaLH?w=118&h=180&c=7&r=0&o=5&dpr=2&pid=1.7', 'Ethan Hunt and his team race against time to prevent a global catastrophe.', 'https://www.youtube.com/watch?v=wb49-oV0F78', 147, 'English', 'USA', 791000000, 7.7),
('The Dark Knight', 2008, 'Christopher Nolan', 'https://th.bing.com/th/id/OIP.NN9rKH-vZbFgtH4FuoW7OwHaLH?w=118&h=180&c=7&r=0&o=5&dpr=2&pid=1.7', 'Batman faces off against the Joker in a battle for Gotham Citys soul.', 'https://www.youtube.com/watch?v=EXeTwQWrcwY', 152, 'English', 'USA', 1004558444, 9.0),
('The Hangover', 2009, 'Todd Phillips', 'https://upload.wikimedia.org/wikipedia/en/b/b9/Hangoverposter09.jpg', 'A hilarious comedy about a group of friends who wake up after a wild bachelor party in Las Vegas with no memory of the night before.', 'https://www.youtube.com/watch?v=vhFVZsk3XpM', 100, 'English', 'USA', 467000000, 7.7),
('Superbad', 2007, 'Greg Mottola', 'https://image.tmdb.org/t/p/original/gqHRD8qNSM2dw3AEeDw2VrJsyiO.jpg', 'A coming-of-age comedy about two high school friends trying to have one last wild night before graduation.', 'https://www.youtube.com/watch?v=SbXk6s7PQE0', 113, 'English', 'USA', 169000000, 7.6),
('Liar Liar', 1997, 'Tom Shadyac', 'https://th.bing.com/th/id/OIP._1Yikjdk8lWXMCGcU-YdmAHaLH?rs=1&pid=ImgDetMain', 'A funny tale of a lawyer who, due to his sons wish, is forced to tell the truth for 24 hours, leading to chaotic situations.', 'https://www.youtube.com/watch?v=5GGRyoVabJw', 86, 'English', 'USA', 181000000, 6.9),
('عسل أسود', 2010, 'Sherif Arafa', 'https://blogs.dickinson.edu/arabic/files/2019/11/assal-eswed-IbDb.jpg', 'A heartwarming comedy-drama about a young man navigating life, love, and family in modern Egypt.', 'https://www.youtube.com/watch?v=9nJ9ALOrFfY', 120, 'Arabic', 'Egypt', 25000000, 7.0),
('اللمبي 8 جيجا', 2010, 'Sherif Arafa', 'https://th.bing.com/th/id/R.483061163edc7e3a808ccbc370926795?rik=FVpRAL9VClR3iQ&pid=ImgRaw&r=0', 'A popular Egyptian comedy about the misadventures of a quirky character known as "El-Limby".', 'https://www.youtube.com/watch?v=9nJ9ALOrFfY', 110, 'Arabic', 'Egypt', 20000000, 6.8),
('Bridesmaids', 2011, 'Paul Feig', 'https://th.bing.com/th/id/OIP.63v0329UqpCGWdFybwiKegHaK9?rs=1&pid=ImgDetMain', 'A group of bridesmaids navigate the ups and downs of wedding planning, leading to hilarious and chaotic situations.', 'https://www.youtube.com/watch?v=F6RzD7ZtFIs', 125, 'English', 'USA', 288000000, 6.8),
('Forrest Gump', 1994, 'Robert Zemeckis', 'https://image.tmdb.org/t/p/original/saHP97rTPS5eLmrLQEcANmKrsFl.jpg', 'A heartwarming drama about a man with a low IQ who inadvertently influences several historical events in the 20th century.', 'https://www.youtube.com/watch?v=bLvqoHBptjg', 142, 'English', 'USA', 678000000, 8.8),
('The Pursuit of Happyness', 2006, 'Gabriele Muccino', 'https://th.bing.com/th/id/R.20870a545d106683263aabf5847dd369?rik=aqBKaR85M3dr9Q&pid=ImgRaw&r=0', 'An inspiring drama based on the true story of Chris Gardner, who overcomes homelessness to achieve success.', 'https://www.youtube.com/watch?v=89Kq8SDIzP0', 117, 'English', 'USA', 307000000, 8.0),
('Interstellar', 2014, 'Christopher Nolan', 'https://th.bing.com/th/id/OIP.IuuohBMqKkT8LCNUWL4W3QHaJQ?rs=1&pid=ImgDetMain', 'A visually stunning sci-fi epic about a group of astronauts traveling through a wormhole to find a new home for humanity.', 'https://www.youtube.com/watch?v=zSWdZVtXT7E', 169, 'English', 'USA', 677000000, 8.6),
('The Shawshank Redemption', 1994, 'Frank Darabont', 'https://image.tmdb.org/t/p/original/q6y0Go1tsGEsmtFryDOJo3dEmqu.jpg', 'A powerful drama about hope and friendship, following two prisoners as they navigate life in Shawshank Prison.', 'https://www.youtube.com/watch?v=6hB3S9bIaco', 142, 'English', 'USA', 28000000, 9.3),
('Blade Runner', 1982, 'Ridley Scott', 'https://th.bing.com/th/id/R.c203a8b403a072511b0c445717c71d49?rik=Ba01%2b5CT19EHbQ&pid=ImgRaw&r=0', 'A classic sci-fi film about a blade runner tasked with hunting down rogue replicants in a dystopian future.', 'https://www.youtube.com/watch?v=81n0T6M9PH0', 117, 'English', 'USA', 33000000, 8.1),
('Her', 2013, 'Spike Jonze', 'https://th.bing.com/th/id/OIP.sSkQwAflFCd07jvQAL_WigHaK9?rs=1&pid=ImgDetMain', 'A futuristic romance about a man who falls in love with an AI assistant, exploring themes of love and technology.', 'https://www.youtube.com/watch?v=WzV6mXIOVl4', 126, 'English', 'USA', 48000000, 8.0),
('The Martian', 2015, 'Ridley Scott', 'https://th.bing.com/th/id/R.47ac110dbea6b55ebbadace5d132a1a5?rik=PhH7Kbd6dX3LMQ&riu=http%3a%2f%2fwww.scifinow.co.uk%2fwp-content%2fuploads%2f2015%2f08%2fThe-Martian-Launch-One-Sheet.jpg&ehk=MmvCgWX3nwG9Z0u2SlriCgLffDSA%2bQc6av%2fu5R%2ba06E%3d&risl=1&pid=ImgRaw&r=0', 'A thrilling sci-fi adventure about an astronaut stranded on Mars who must use his ingenuity to survive and signal Earth.', 'https://www.youtube.com/watch?v=ej3ioOneTy8', 144, 'English', 'USA', 630000000, 8.0),
('Star Wars', 1977, 'George Lucas', 'https://lumiere-a.akamaihd.net/v1/images/p_starwarstheriseofskywalker_18508_3840c966.jpeg', 'An iconic space opera about the battle between the Rebel Alliance and the evil Galactic Empire.', 'https://www.youtube.com/watch?v=1g3_CFmnU7k', 121, 'English', 'USA', 775000000, 8.6),
('Guardians of the Galaxy', 2014, 'James Gunn', 'https://th.bing.com/th/id/OIP.4E32Anj4RLBR4T3KZGh9cgHaLH?rs=1&pid=ImgDetMain', 'A fun and action-packed adventure about a group of misfits who team up to save the galaxy.', 'https://www.youtube.com/watch?v=d96cjJhvlMA', 121, 'English', 'USA', 772000000, 8.0),
('Ex Machina', 2014, 'Alex Garland', 'https://th.bing.com/th/id/R.86cc8c3afb01528c1b16fe317f83e96e?rik=1AIlKzxU5gyrVw&riu=http%3a%2f%2fmedia.senscritique.com%2fmedia%2f000009739381%2fsource_big%2fEx_Machina.jpg&ehk=lyruDKiOzMw8wNvahDK%2bn9LP6HcMWvY5bc%2boYvp2GO4%3d&risl=&pid=ImgRaw&r=0', 'A thought-provoking sci-fi thriller about artificial intelligence and the nature of consciousness.', 'https://www.youtube.com/watch?v=XYGz6Pa5y34', 108, 'English', 'USA', 36000000, 7.7),
('The Conjuring', 2013, 'James Wan', 'https://th.bing.com/th/id/R.6c5076a86ded914e19fad1bf5c574ed7?rik=wJfI9B80oK17Sw&pid=ImgRaw&r=0', 'A chilling horror film based on the true story of paranormal investigators Ed and Lorraine Warren.', 'https://www.youtube.com/watch?v=k10ETZ41Kfc', 112, 'English', 'USA', 319000000, 7.5),
('Get Out', 2017, 'Jordan Peele', 'https://th.bing.com/th/id/OIP.LoS90_PIVakLofN3YjKp9gAAAA?rs=1&pid=ImgDetMain', 'A psychological horror film about a young African-American man who uncovers a disturbing secret while visiting his girlfriend’s family.', 'https://www.youtube.com/watch?v=sRfnevzM9kQ', 104, 'English', 'USA', 255000000, 7.7),
('A Quiet Place', 2018, 'John Krasinski', 'https://th.bing.com/th/id/R.75ed67c14e6231f9ea43d72a64447038?rik=eKBJy5obhooANA&pid=ImgRaw&r=0', 'A family must live in silence to avoid mysterious creatures that hunt by sound.', 'https://www.youtube.com/watch?v=WR7cc5t7tv8', 90, 'English', 'USA', 340000000, 7.5),
('The Shining', 1980, 'Stanley Kubrick', 'https://th.bing.com/th/id/R.3ddb001f775dd13144e24aa998d7634f?rik=LRpl6sUsmYT1EA&pid=ImgRaw&r=0', 'A psychological horror classic about a familys descent into madness while isolated in a haunted hotel.', 'https://www.youtube.com/watch?v=5Cb3ik6zP2I', 146, 'English', 'USA', 47000000, 8.4),
('Hereditary', 2018, 'Ari Aster', 'https://image.tmdb.org/t/p/original/4GFPuL14eXi66V96xBWY73Y9PfR.jpg', 'A deeply unsettling horror film about a family haunted by a sinister presence after the death of their secretive grandmother.', 'https://www.youtube.com/watch?v=NKtZj6rkHkQ', 127, 'English', 'USA', 79000000, 7.3),
('Back to the Future', 1985, 'Robert Zemeckis', 'https://th.bing.com/th/id/R.c947e7604d35c83e274b047c7601e3c7?rik=AisgWk%2bKk8Onvw&riu=http%3a%2f%2fcineplexfiles.s3.amazonaws.com%2fPromos%2fBacktotheFuture%2fBTTF_header2.jpg&ehk=iHI3U4Rfyrsz77JCVLzEZad3MwYtGTzolOUJ%2bMHv888%3d&risl=&pid=ImgRaw&r=0', 'A time-traveling adventure about a teenager who accidentally travels to the past and must ensure his parents fall in love.', 'https://www.youtube.com/watch?v=qvsgGtivCgs', 116, 'English', 'USA', 381000000, 8.5),
('The Secret Life of Walter Mitty', 2013, 'Ben Stiller', 'https://th.bing.com/th/id/R.76e1a80c4093b6723455af5b206ac0a4?rik=CY7jW9U3SvMQmQ&riu=http%3a%2f%2fcdn.collider.com%2fwp-content%2fuploads%2fthe-secret-life-of-walter-mitty-poster-mountain.jpg&ehk=fDea5JjMyIpTy9imegcxxgIOBnFWRq2nUlPgFcYPptI%3d&risl=&pid=ImgRaw&r=0', 'An uplifting adventure about a daydreamer who embarks on a real-life journey to find a missing photograph.', 'https://www.youtube.com/watch?v=QKzF9F5rrqk', 114, 'English', 'USA', 190000000, 7.3),
('Chef', 2014, 'Jon Favreau', 'https://www.themoviedb.org/t/p/original/7PNH638Qx34cLZ1ZZTpLgOsxeAS.jpg', 'A heartwarming comedy about a chef who rediscovers his passion for food and life through a cross-country food truck journey.', 'https://www.youtube.com/watch?v=Gz6lQv8Jw3Y', 115, 'English', 'USA', 45000000, 7.3),
('Jumanji: Welcome to the Jungle', 2017, 'Jake Kasdan', 'https://th.bing.com/th/id/OIP.wF-OOkfawrih1HO1PQpsjgHaK-?rs=1&pid=ImgDetMain', 'A group of teenagers are transported into a video game and must complete the adventure to return to the real world.', 'https://www.youtube.com/watch?v=2QKg5SZ_35I', 119, 'English', 'USA', 962000000, 6.9),
('Indiana Jones and the Raiders of the Lost Ark', 1981, 'Steven Spielberg', 'https://th.bing.com/th?id=OIF.NSqPA%2bGDVjerjpMhtjOufA&rs=1&pid=ImgDetMain', 'An iconic adventure film about archaeologist Indiana Jones as he races to find the Ark of the Covenant before the Nazis.', 'https://www.youtube.com/watch?v=5RgIorD1mQU', 115, 'English', 'USA', 248000000, 8.4),
('The Fault in Our Stars', 2014, 'Josh Boone', 'https://th.bing.com/th/id/R.9e0188f4ae1fd57bdd544e6256b37684?rik=v1BAbRH5VVwRpg&riu=http%3a%2f%2fimg2.wikia.nocookie.net%2f__cb20140416094218%2fthefaultinourstars%2fimages%2f5%2f5c%2fThe_Fault_In_Our_Stars_Soundtrack_Cover.jpg&ehk=uynAhTdpKEOOFP8pA%2fVZhTps6p9OmcxJkX2IZblNL1Y%3d&risl=&pid=ImgRaw&r=0', 'A touching romance about two teenagers with cancer who fall in love and embark on a life-changing journey.', 'https://www.youtube.com/watch?v=9ItBvH5J6ss', 126, 'English', 'USA', 307000000, 7.7),
('Titanic', 1997, 'James Cameron', 'https://th.bing.com/th/id/R.68c12203b7e600cd1b1ed08ea5e9706e?rik=QaoppBMEt%2fSO8w&pid=ImgRaw&r=0', 'A romantic drama about a young aristocratic woman and a penniless artist who fall in love aboard the R.M.S. Titanic, only to face tragedy when the ship meets its fateful end.', 'https://www.youtube.com/watch?v=kVrqfYjkTdQ', 195, 'English', 'USA', 2200000000, 7.8),
('Venom', 2018, 'Ruben Fleischer', 'https://th.bing.com/th/id/R.38b5532c5ea0b94e92182080bd0c1f18?rik=lS5a1IEVoylZZw&pid=ImgRaw&r=0', 'A dark sci-fi action film about a journalist who bonds with an alien symbiote, gaining superhuman powers while struggling to control the entity’s violent impulses.', 'https://www.youtube.com/watch?v=u9Mv98Gr5pY', 112, 'English', 'USA', 856000000, 6.7),
('أنت عمري', 2003, 'Maged El-Kedwany', 'https://th.bing.com/th/id/OIP.ijMQgEtaseuxMKUn1CGMswHaEK?rs=1&pid=ImgDetMain', 'A classic Egyptian romance that has captivated audiences with its emotional depth and poetic narrative.', 'https://www.youtube.com/watch?v=blKq5imQ28Q', 90, 'Arabic', 'Egypt', 7000000, 7.0);

-- SQL Insert statements for movie_genres table
INSERT INTO movie_genres (MOVIE_ID, GENRE) VALUES
-- The Lunar Eclipse
(1, 'Thriller'),
(1, 'Sci-Fi'),
(1, 'Mystery'),

-- Whispers of Autumn  
(2, 'Drama'),
(2, 'Romance'),

-- Digital Wasteland
(3, 'Sci-Fi'),
(3, 'Action'),
(3, 'Adventure'),

-- Midnight in Barcelona
(4, 'Romance'),
(4, 'Comedy'),

-- The Hidden Fortress
(5, 'Adventure'),
(5, 'Action'),
(5, 'Historical'),

-- Quantum Paradox
(6, 'Sci-Fi'),
(6, 'Thriller'),
(6, 'Mystery'),

-- Echoes of History
(7, 'Drama'),
(7, 'War'),
(7, 'Historical'),

-- Desert Mirage
(8, 'Adventure'),
(8, 'Action'),

-- Neon Dreams
(9, 'Thriller'),
(9, 'Sci-Fi'),
(9, 'Mystery'),

-- The Art of Deception
(10, 'Crime'),
(10, 'Thriller'),
(10, 'Drama'),

-- Winter's Promise
(11, 'Drama'),
(11, 'Family'),

-- Rhythm of the Streets
(12, 'Musical'),
(12, 'Drama'),

-- Crystal Skies
(13, 'Action'),
(13, 'Disaster'),
(13, 'Sci-Fi'),

-- Beyond the Horizon
(14, 'Sci-Fi'),
(14, 'Drama'),
(14, 'Adventure'),

-- The Chef's Letter
(15, 'Drama'),
(15, 'Culinary'),

-- Shadow Kingdom
(16, 'Fantasy'),
(16, 'Adventure'),
(16, 'Action'),

-- The Final Equation
(17, 'Drama'),
(17, 'Biography'),

-- Ocean's Memory
(18, 'Documentary'),
(18, 'Environmental'),

-- Midnight Sonata
(19, 'Drama'),
(19, 'Music'),

-- Urban Legends
(20, 'Horror'),
(20, 'Anthology'),
(20, 'Thriller');

-- SQL Insert statements for actors table
INSERT INTO actors (NAME, GENDER, DATE_OF_BIRTH, NATIONALITY, BIO, IMAGEURL) VALUES
('Emily Martinez', 'Female', '1988-04-15', 'American', 'Emmy-winning actress known for her versatile performances across drama and sci-fi genres. Started her career in indie films before breaking into mainstream success.', 'https://example.com/actors/emily_martinez.jpg'),
('Michael Chen', 'Male', '1985-09-22', 'Canadian', 'Former stunt performer who transitioned to leading roles in action films. Known for performing many of his own stunts and his dedicated physical training regimen.', 'https://example.com/actors/michael_chen.jpg'),
('Sophia Patel', 'Female', '1990-07-03', 'British-Indian', 'BAFTA-nominated actress who gained fame through period dramas before expanding to contemporary thrillers. Trained at the Royal Academy of Dramatic Art.', 'https://example.com/actors/sophia_patel.jpg'),
('Carlos Rodriguez', 'Male', '1979-12-10', 'Spanish', 'Acclaimed actor and director who has worked extensively in Spanish and international productions. Known for his intense method acting approach.', 'https://example.com/actors/carlos_rodriguez.jpg'),
('Yuki Tanaka', 'Female', '1992-05-28', 'Japanese', 'Rising star in Japanese cinema who has gained international recognition for her emotional depth and expressive performances. Also works as a voice actress for animated films.', 'https://example.com/actors/yuki_tanaka.jpg'),
('Daniel Okafor', 'Male', '1983-02-17', 'Nigerian', 'Versatile actor who has starred in both Nollywood and Hollywood productions. Known for his commanding presence and distinctive voice.', 'https://example.com/actors/daniel_okafor.jpg'),
('Astrid Larsson', 'Female', '1986-11-09', 'Swedish', 'Critically acclaimed actress who has won multiple European Film Awards. Known for her work in psychological dramas and minimalist performances.', 'https://example.com/actors/astrid_larsson.jpg'),
('Robert Williams', 'Male', '1975-08-20', 'American', 'Veteran character actor with over 50 film credits. Known for his ability to transform completely into diverse roles and his distinctive supporting performances.', 'https://example.com/actors/robert_williams.jpg'),
('Marie Dubois', 'Female', '1994-01-12', 'French', 'Former model who successfully transitioned to acting. Known for her work in romantic comedies and period dramas in French cinema.', 'https://example.com/actors/marie_dubois.jpg'),
('Ahmed Hassan', 'Male', '1989-06-25', 'Egyptian', 'Award-winning actor known for his work in both commercial blockbusters and art house films across the Middle East. Trained in theatre before moving to film.', 'https://example.com/actors/ahmed_hassan.jpg'),
('Park Ji-won', 'Female', '1991-03-18', 'South Korean', 'Rising K-drama star who has successfully crossed over into film. Known for her emotional range and popularity in romantic dramas.', 'https://example.com/actors/park_jiwon.jpg'),
('Thomas Schmidt', 'Male', '1982-10-05', 'German', 'European Film Award winner known for his intense performances in dramatic and historical films. Started his career in theatre productions.', 'https://example.com/actors/thomas_schmidt.jpg'),
('Isabella Rossi', 'Female', '1980-07-14', 'Italian', "Versatile actress and filmmaker who has worked with some of Europe\'s most acclaimed directors. Known for her natural acting style and ability to work in multiple languages.", 'https://example.com/actors/isabella_rossi.jpg'),
('James Thompson', 'Male', '1976-04-30', 'Australian', 'Former action star who has successfully transitioned to dramatic roles. Known for his physical presence and emotional depth in recent performances.', 'https://example.com/actors/james_thompson.jpg'),
('Ana Silva', 'Female', '1993-09-08', 'Brazilian', 'Breakout star from Brazilian cinema who has recently begun appearing in international productions. Known for her dynamic performances and dancing background.', 'https://example.com/actors/ana_silva.jpg'),
('Viktor Petrov', 'Male', '1984-12-03', 'Russian', 'Acclaimed actor known for his intense method acting and commitment to challenging roles. Has worked in both commercial and art house productions.', 'https://example.com/actors/viktor_petrov.jpg'),
('Liu Wei', 'Female', '1987-05-20', 'Chinese', 'Award-winning actress who has starred in both blockbuster action films and intimate character dramas. Known for her martial arts training and dramatic range.', 'https://example.com/actors/liu_wei.jpg'),
('Omar Farhan', 'Male', '1990-02-11', 'Lebanese', 'Multilingual actor who has appeared in productions across the Middle East, Europe, and North America. Known for his charismatic presence and dramatic intensity.', 'https://example.com/actors/omar_farhan.jpg'),
('Priya Sharma', 'Female', '1989-11-27', 'Indian', 'Acclaimed Bollywood actress who has successfully crossed over into international cinema. Known for her expressive performances and dancing abilities.', 'https://example.com/actors/priya_sharma.jpg'),
('David Wilson', 'Male', '1978-06-15', 'British', 'Stage-trained actor known for his work in period dramas and literary adaptations. Has won multiple theatre awards before focusing on film.', 'https://example.com/actors/david_wilson.jpg'),
('Elena Popov', 'Female', '1985-03-22', 'Ukrainian', 'Versatile actress who has worked across Eastern European and Western productions. Known for her striking screen presence and dramatic intensity.', 'https://example.com/actors/elena_popov.jpg'),
('Rafael Mendez', 'Male', '1992-08-14', 'Mexican', 'Rising star in Latin American cinema who has recently broken into Hollywood productions. Known for his charismatic performances in dramas and action films.', 'https://example.com/actors/rafael_mendez.jpg'),
('Zoe Bennett', 'Female', '1983-10-29', 'New Zealander', 'Versatile actress known for her work in fantasy and adventure films. Has extensive stunt training and often performs her own action sequences.', 'https://example.com/actors/zoe_bennett.jpg'),
('Hiroshi Nakamura', 'Male', '1977-01-05', 'Japanese', 'Veteran actor who has become an institution in Japanese cinema. Known for his subtle performances and long-standing collaborations with renowned directors.', 'https://example.com/actors/hiroshi_nakamura.jpg'),
('Amara Diallo', 'Female', '1995-07-19', 'Senegalese', 'Breakthrough talent discovered in an independent film who has since starred in major international productions. Known for her natural charisma and emotional authenticity.', 'https://example.com/actors/amara_diallo.jpg');

-- SQL Insert statements for movie_actors table
INSERT INTO movie_actors (MOVIE_ID, ACTOR_ID, ROLE) VALUES
-- The Lunar Eclipse (Psychological thriller about an astronaut)
(1, 3, 'Dr. Maya Sharma'),
(1, 8, 'Commander Jack Reynolds'),
(1, 16, 'Mission Control Director Alexei Kuznetsov'),

-- Whispers of Autumn (Drama about a writer returning home)
(2, 1, 'Elizabeth Chen'),
(2, 2, 'David Chen'),
(2, 7, 'Ingrid Nilsson'),

-- Digital Wasteland (Dystopian sci-fi)
(3, 14, 'Captain Marcus Reeve'),
(3, 17, 'Commander Lin Mei'),
(3, 6, 'Dr. Kwame Osei'),
(3, 21, 'Elena Volkova'),

-- Midnight in Barcelona (Romantic comedy)
(4, 4, 'Javier Moreno'),
(4, 9, 'Sophie Laurent'),
(4, 22, 'Diego Alvarez'),

-- The Hidden Fortress (Remake of classic tale)
(5, 5, 'Princess Yuki'),
(5, 24, 'General Rokurota Makabe'),
(5, 8, 'Peasant Tahei'),

-- Quantum Paradox (Mind-bending sci-fi)
(6, 12, 'Professor Heinrich Weber'),
(6, 1, 'Dr. Sarah Connor'),
(6, 20, 'Dr. William Blake'),
(6, 11, 'Min-ji Park'),

-- Echoes of History (WWII drama)
(7, 9, 'Resistance leader Camille Dubois'),
(7, 12, 'Major Klaus Hoffman'),
(7, 13, 'Resistance fighter Lucia Moretti'),

-- Desert Mirage (Adventure in the Sahara)
(8, 10, 'Expedition leader Faisal Hassan'),
(8, 18, 'Archaeologist Karim Nassar'),
(8, 23, 'Explorer Kaia Bennett'),

-- Neon Dreams (Futuristic thriller in Seoul)
(9, 11, 'Detective Soo-jin Park'),
(9, 2, 'Tech specialist Alex Kim'),
(9, 17, 'Mysterious hacker "Ghost"'),

-- The Art of Deception (Heist film)
(10, 20, 'Former art forger Jonathan Pierce'),
(10, 3, 'FBI Agent Leila Khan'),
(10, 16, 'Art thief Nikolai Volkov'),

-- Winter's Promise (Family drama in Sweden)
(11, 7, 'Eldest sister Elsa Johansson'),
(11, 12, 'Brother Erik Johansson'),
(11, 21, 'Youngest sister Katja Johansson'),

-- Rhythm of the Streets (Dance drama in Rio)
(12, 15, 'Dance prodigy Luiza Freitas'),
(12, 22, 'Dance instructor Miguel Santos'),
(12, 19, 'Rival dancer Nikita Sharma'),

-- Crystal Skies (Disaster film)
(13, 14, 'Climate scientist Dr. Ryan Cooper'),
(13, 23, 'Meteorologist Dr. Emma Wilson'),
(13, 6, 'Government official Samuel Johnson'),

-- Beyond the Horizon (Mars mission drama)
(14, 1, 'Mission Commander Jessica Reynolds'),
(14, 2, 'Flight Engineer David Zhang'),
(14, 20, 'NASA Director Robert Glendale'),
(14, 25, 'Medical Officer Amina Diallo'),

-- The Chef's Letter (Culinary drama)
(15, 9, 'Chef Mathieu Laurent'),
(15, 13, 'Sous-chef Bianca Conti'),
(15, 4, 'Restaurant critic Antonio Ferrer'),

-- Shadow Kingdom (Fantasy epic)
(16, 23, 'Warrior princess Freya'),
(16, 6, 'King Obadiah'),
(16, 17, 'Mystic warrior Mei-Ling'),
(16, 18, 'Desert prince Karim'),

-- The Final Equation (Mathematical drama)
(17, 3, 'Mathematician Dr. Arya Reddy'),
(17, 20, 'Professor Richard Hargreaves'),
(17, 19, 'Graduate student Anjali Das'),

-- Ocean's Memory (Environmental documentary)
(18, 14, 'Narrator'),
(18, 25, 'Oceanographer Dr. Nadia Diallo'),

-- Midnight Sonata (Drama about deaf pianist)
(19, 12, 'Pianist Thomas Werner'),
(19, 7, 'Music teacher Helga Lindström'),
(19, 13, 'Wife Giovanna Werner'),

-- Urban Legends (Horror anthology)
(20, 1, 'Professor of Folklore'),
(20, 8, 'Detective Frank Murphy'),
(20, 11, 'College student Ji-young'),
(20, 22, 'Urban explorer Carlos'),
(20, 5, 'Ghost of Hanako');