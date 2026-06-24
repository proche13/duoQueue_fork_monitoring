DELIMITER $$

CREATE PROCEDURE GetMatchMessages(
    IN p_match_id INT,
    IN p_limit INT,
    IN p_offset INT
)

BEGIN
    SELECT 
        m.message_id,
        m.message,
        m.created_timestamp,
        m.sender_id,
        CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
        m.receiver_id
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.match_id = p_match_id
    ORDER BY m.created_timestamp ASC
    LIMIT p_limit OFFSET p_offset;

END$$

CREATE PROCEDURE GetUserProfile(
    IN p_user_id INT
)

BEGIN
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.created_timestamp,
        up.location,
        up.profile_photo,
        up.date_of_birth,
        up.gender,
        up.seeking,
        up.about_me,
        up.smoker,
        up.drinker
    FROM users u
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.user_id = p_user_id;

    SELECT photo
    FROM user_photos
    WHERE user_id = p_user_id;

    SELECT ag.game_id, ag.game_name
    FROM users_games ug
    JOIN available_games ag ON ug.game_id = ag.game_id
    WHERE ug.user_id = p_user_id;

    SELECT ap.platform_name, upl.platform_username
    FROM user_platforms upl
    JOIN available_platforms ap ON upl.platform_id = ap.platform_id
    WHERE upl.user_id = p_user_id;

END$$

CREATE PROCEDURE GetSharedGames(
    IN p_user1_id INT,
    IN p_user2_id INT
)
BEGIN
    SELECT ag.game_id, ag.game_name
    FROM users_games u1
    JOIN users_games u2 ON u1.game_id = u2.game_id
    JOIN available_games ag ON u1.game_id = ag.game_id
    WHERE u1.user_id = p_user1_id
      AND u2.user_id = p_user2_id;
END$$

CREATE PROCEDURE GetMatchmakingCandidates(IN p_user_id INT, IN p_limit INT)
BEGIN

  SELECT
    u.user_id,
    u.first_name,
    u.last_name,
    up.profile_photo,
    up.about_me,
    up.location,
    up.gender,
    up.seeking,
    up.date_of_birth,

    (
      COALESCE(games_score.pts, 0)
      + COALESCE(plat_score.pts, 0)
      + IF(up.gender = seeker.seeking, 20, 0)
      + CASE
          WHEN ABS(YEAR(NOW()) - YEAR(up.date_of_birth)) <= 5 THEN 15
          WHEN ABS(YEAR(NOW()) - YEAR(up.date_of_birth)) <= 10 THEN 5
          ELSE 0
        END
      + IF(up.location = seeker.location, 5, 0)
    ) AS match_score

  FROM users u
  JOIN user_profiles up ON u.user_id = up.user_id

  JOIN (
    SELECT gender, seeking, location, date_of_birth
    FROM user_profiles
    WHERE user_id = p_user_id
  ) AS seeker ON 1=1

  LEFT JOIN (
    SELECT ug2.user_id, COUNT(*) * 10 AS pts
    FROM users_games ug1
    JOIN users_games ug2 ON ug1.game_id = ug2.game_id
    WHERE ug1.user_id = p_user_id
    GROUP BY ug2.user_id
  ) AS games_score ON games_score.user_id = u.user_id

  LEFT JOIN (
    SELECT up2.user_id, COUNT(*) * 5 AS pts
    FROM user_platforms up1
    JOIN user_platforms up2 ON up1.platform_id = up2.platform_id
    WHERE up1.user_id = p_user_id
    GROUP BY up2.user_id
  ) AS plat_score ON plat_score.user_id = u.user_id

  WHERE u.user_id <> p_user_id
    AND u.is_banned = 0
    AND u.user_id NOT IN (
        SELECT liked_user_id FROM likes WHERE user_id = p_user_id
    )
    AND u.user_id NOT IN (
        SELECT disliked_user_id FROM dislikes WHERE user_id = p_user_id
    )
    AND u.user_id NOT IN (
        SELECT user2_id FROM matches WHERE user1_id = p_user_id
        UNION
        SELECT user1_id FROM matches WHERE user2_id = p_user_id
    )

  ORDER BY match_score DESC
  LIMIT p_limit;

END$$

DELIMITER ;
