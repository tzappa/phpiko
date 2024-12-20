-- DROP TABLE IF EXISTS counters;

CREATE TABLE IF NOT EXISTS counters
(
    id                 VARCHAR(32) NOT NULL PRIMARY KEY,
    current            INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
COMMENT ON TABLE counters IS 'multipurpose counter - eg. for posts views, comments, likes, etc.';
COMMENT ON COLUMN counters.id IS 'eg. post_2424_views - combination of the name of the object, ID and the action';
