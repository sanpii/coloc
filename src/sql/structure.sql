CREATE TABLE person (
    id serial PRIMARY KEY,
    name character varying NOT NULL,
    password character varying NOT NULL,
    email character varying NOT NULL
);

CREATE TABLE payment (
    id serial PRIMARY KEY,
    done BOOLEAN DEFAULT false,
    created timestamp without time zone DEFAULT now() NOT NULL
);

CREATE TABLE expense (
    id serial PRIMARY KEY,
    person_id INTEGER NOT NULL REFERENCES person,
    price REAL NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    shop character varying NOT NULL,
    description character varying,
    payment_id INTEGER REFERENCES payment
);
