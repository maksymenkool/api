-- Database: main

-- Automatically change "updated" column
CREATE OR REPLACE FUNCTION process_updated_column()
  RETURNS TRIGGER AS $$
BEGIN
  NEW.updated = now();
  RETURN NEW;
END;
$$ LANGUAGE 'plpgsql';


-- Expirable Storage


CREATE TABLE expirable_storage
(
  id SERIAL NOT NULL PRIMARY KEY ,
  serialized_object TEXT NOT NULL,
  token TEXT NOT NULL ,
  expires TIMESTAMP,
  created TIMESTAMP DEFAULT now()
);


-- Users


CREATE TABLE users
(
  id SERIAL NOT NULL PRIMARY KEY ,
  email TEXT NOT NULL UNIQUE,
  email_confirmed BOOLEAN DEFAULT FALSE,
  password TEXT,
  first_name TEXT,
  last_name TEXT,
  picture TEXT,
  created TIMESTAMP DEFAULT now(),
  updated TIMESTAMP
);

CREATE TRIGGER users_before_update BEFORE UPDATE
ON users FOR EACH ROW EXECUTE PROCEDURE
  process_updated_column();


-- Sessions


CREATE TABLE sessions
(
  id SERIAL NOT NULL PRIMARY KEY ,
  user_id INTEGER REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
  token TEXT,
  device TEXT,
  created TIMESTAMP DEFAULT now(),
  updated TIMESTAMP
);

CREATE TRIGGER sessions_before_update BEFORE UPDATE
ON sessions FOR EACH ROW EXECUTE PROCEDURE
  process_updated_column();


-- Travels


CREATE TABLE travels
(
  id SERIAL NOT NULL PRIMARY KEY ,
  user_id INTEGER REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
  title TEXT NOT NULL,
  description TEXT NOT NULL,
  created TIMESTAMP DEFAULT now(),
  updated TIMESTAMP
);

CREATE TRIGGER travels_before_update BEFORE UPDATE
ON travels FOR EACH ROW EXECUTE PROCEDURE
  process_updated_column();


-- Travel comments


CREATE TABLE travel_comments
(
  id SERIAL NOT NULL PRIMARY KEY ,
  author_id INTEGER REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
  travel_id INTEGER REFERENCES travels (id) ON UPDATE CASCADE ON DELETE CASCADE,
  text TEXT NOT NULL,
  created TIMESTAMP DEFAULT now(),
  updated TIMESTAMP
);

CREATE TRIGGER travel_comments_before_update BEFORE UPDATE
ON travel_comments FOR EACH ROW EXECUTE PROCEDURE
  process_updated_column();



-- IATA


CREATE TABLE iata_carriers
(
  code TEXT NOT NULL PRIMARY KEY,
  type TEXT NOT NULL,
  name TEXT NOT NULL,
  CONSTRAINT code_regex CHECK (code ~* '^[0-9A-Z]{2}$'),
  CONSTRAINT type_regex CHECK (type ~* '^aircraft|bus|railway|vendor$')

);

CREATE TABLE iata_countries
(
  code TEXT NOT NULL PRIMARY KEY,
  name TEXT NOT NULL,
  phone INTEGER,
  currency_code TEXT,
  CONSTRAINT code_regex CHECK (code ~* '^[0-9A-Z]{2}$')
);

CREATE TABLE iata_states (
  code         TEXT NOT NULL,
  country_code TEXT NOT NULL,
  name         TEXT NOT NULL,
  PRIMARY KEY (code, country_code),
  CONSTRAINT country_fk FOREIGN KEY (country_code)
  REFERENCES iata_countries (code)
  ON UPDATE NO ACTION
  ON DELETE NO ACTION,
  CONSTRAINT code_regex CHECK (code ~* '^[0-9A-Z]{2,3}$')
);

CREATE TABLE iata_cities
(
  code TEXT NOT NULL PRIMARY KEY ,
  country_code TEXT NOT NULL,
  state_code TEXT,
  lat FLOAT4 NOT NULL,
  lon FLOAT4 NOT NULL,
  capital BOOLEAN DEFAULT false NOT NULL,
  name TEXT NOT NULL,
  utc TEXT,
  CONSTRAINT code_regex CHECK (code ~* '^[0-9A-Z]{3}$'),

  CONSTRAINT country_fk FOREIGN KEY (country_code)
  REFERENCES iata_countries (code)
  ON UPDATE NO ACTION
  ON DELETE NO ACTION,

  CONSTRAINT state_fk FOREIGN KEY (country_code, state_code)
  REFERENCES iata_states (country_code, code) MATCH SIMPLE
  ON UPDATE NO ACTION
  ON DELETE NO ACTION

);

CREATE TABLE iata_ports
(
  code TEXT PRIMARY KEY NOT NULL,
  city_code TEXT NOT NULL,
  name TEXT,
  lat FLOAT4 NOT NULL,
  lon FLOAT4 NOT NULL,
  type TEXT NOT NULL,


  CONSTRAINT city_fk FOREIGN KEY (city_code)
  REFERENCES iata_cities (code)
  ON UPDATE NO ACTION
  ON DELETE NO ACTION,

  CONSTRAINT code_regex CHECK (code ~* '^[0-9A-Z]{3}$'),
  CONSTRAINT type_regex CHECK (type ~* '^airport|bus|helicopter|railway|seaport$')
);
