
CREATE TABLE users ( 
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_banned BOOLEAN DEFAULT FALSE,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_profiles ( 
    user_id INT PRIMARY KEY,
    location VARCHAR(255),
    profile_photo VARCHAR(255),
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    seeking ENUM('Male', 'Female', 'Other') NOT NULL,
    about_me TEXT,
    smoker BOOLEAN,
    drinker BOOLEAN,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE user_photos ( 
    user_id INT NOT NULL,
    photo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_user_photos_user_id ON user_photos(user_id);

CREATE TABLE available_genres (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE available_games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE game_genres ( 
    game_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (game_id, genre_id),
    FOREIGN KEY (game_id) REFERENCES available_games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES available_genres(genre_id) ON DELETE CASCADE
);

CREATE INDEX idx_game_genres_game_id ON game_genres(game_id);
CREATE INDEX idx_game_genres_genre_id ON game_genres(genre_id);

CREATE TABLE users_games ( 
    user_id INT NOT NULL, 
    game_id INT NOT NULL,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES available_games(game_id) ON DELETE CASCADE
);

CREATE INDEX idx_users_games_user_id ON users_games(user_id);
CREATE INDEX idx_users_games_game_id ON users_games(game_id);

CREATE TABLE available_platforms ( 
    platform_id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE user_platforms ( 
    user_id INT NOT NULL,
    platform_id INT NOT NULL,
    platform_username VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id, platform_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES available_platforms(platform_id) ON DELETE CASCADE
);

CREATE INDEX idx_user_platforms_user_id ON user_platforms(user_id);
CREATE INDEX idx_user_platforms_platform_id ON user_platforms(platform_id);

CREATE TABLE likes ( 
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    liked_user_id INT NOT NULL,
    status ENUM('PROCESSING', 'MATCHED', 'REJECTED') NOT NULL DEFAULT 'PROCESSING',
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (liked_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CHECK (user_id <> liked_user_id),
    UNIQUE (user_id, liked_user_id)
);

CREATE INDEX idx_likes_user_id ON likes(user_id);
CREATE INDEX idx_likes_liked_user_id ON likes(liked_user_id);

CREATE TABLE dislikes ( 
    dislike_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    disliked_user_id INT NOT NULL,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (disliked_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CHECK (user_id <> disliked_user_id),
    UNIQUE (user_id, disliked_user_id)
);

CREATE INDEX idx_dislikes_user_id ON dislikes(user_id);
CREATE INDEX idx_dislikes_disliked_user_id ON dislikes(disliked_user_id);

CREATE TABLE matches ( 
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CHECK (user1_id <> user2_id),
    UNIQUE (user1_id, user2_id)
);

CREATE INDEX idx_matches_user1_id ON matches(user1_id);
CREATE INDEX idx_matches_user2_id ON matches(user2_id);

CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    message TEXT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Foreign Key (match_id) REFERENCES matches(match_id),
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

CREATE INDEX idx_messages_match_id ON messages(match_id);
CREATE INDEX idx_messages_sender_id ON messages(sender_id);
CREATE INDEX idx_messages_receiver_id ON messages(receiver_id);

CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporting_user_id INT NOT NULL,
    reported_user_id INT NOT NULL,
    reason TEXT,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporting_user_id) REFERENCES users(user_id),
    FOREIGN KEY (reported_user_id) REFERENCES users(user_id),
    CHECK (reporting_user_id <> reported_user_id)
);

CREATE INDEX idx_reports_reporting_user_id ON reports(reporting_user_id);
CREATE INDEX idx_reports_reported_user_id ON reports(reported_user_id);

CREATE TABLE banned (
    user_id INT PRIMARY KEY,
    admin_id INT NOT NULL,
    reason TEXT NOT NULL,
    ban_duration INT NOT NULL,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_timestamp TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (admin_id) REFERENCES users(user_id),
    CHECK (ban_duration >= 0)
);

CREATE INDEX idx_banned_user_id ON banned(user_id);
CREATE INDEX idx_banned_admin_id ON banned(admin_id);
