-- DROP TABLE IF EXISTS counters;

CREATE TABLE IF NOT EXISTS counters
(
    id                 VARCHAR(255) NOT NULL PRIMARY KEY, -- eg. post_2424_views - combination of the name of the object, ID and the action
    current            INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) COMMENT = 'Multipurpose counter - eg. for views, comments, likes, etc.';
