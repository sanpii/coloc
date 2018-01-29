CREATE TABLE person (
    id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    name CHARACTER VARYING NOT NULL,
    password CHARACTER VARYING NOT NULL,
    email CHARACTER VARYING NOT NULL
);

CREATE TABLE payment (
    id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    done BOOLEAN DEFAULT false,
    created TIMESTAMP WITHOUT TIME ZONE DEFAULT now() NOT NULL
);

CREATE TABLE expense (
    id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    person_id INTEGER NOT NULL REFERENCES person,
    price NUMERIC NOT NULL,
    created TIMESTAMP WITHOUT TIME ZONE DEFAULT now() NOT NULL,
    shop CHARACTER VARYING NOT NULL,
    description CHARACTER VARYING,
    tr INTEGER DEFAULT 0,
    payment_id INTEGER REFERENCES payment
);

CREATE TABLE config (
    key CHARACTER VARYING PRIMARY KEY,
    value CHARACTER VARYING
);
