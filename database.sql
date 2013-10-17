DROP TABLE IF EXISTS aliases;

CREATE TABLE aliases ( id INTEGER, alias TEXT NOT NULL UNIQUE, path TEXT NOT NULL, description TEXT NOT NULL, public INTEGER NOT NULL DEFAULT 0, PRIMARY KEY(id) );

INSERT INTO aliases VALUES ( NULL, 'mgr', 'config.db', '', 0 );


DROP TABLE IF EXISTS users;

CREATE TABLE users ( id INTEGER, username TEXT NOT NULL DEFAULT '', password TEXT NOT NULL DEFAULT '', user_type INTEGER NOT NULL DEFAULT 1, PRIMARY KEY (id) );

INSERT INTO users VALUES ( NULL, 'rudie', 'd678e10e7c944dc4ebe23955cce435272f134d5e', 0 );


DROP TABLE IF EXISTS user_alias_access;

CREATE TABLE user_alias_access ( user_id INTEGER, alias_id INTEGER, allowed_queries TEXT NOT NULL DEFAULT 'select,pragma', PRIMARY KEY (user_id, alias_id) );
