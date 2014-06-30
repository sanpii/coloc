insert into person (name, password, email) values ('Nicolas', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==', 'nicolas.joseph@homecomputing.fr');
insert into person (name, password, email) values ('Sandra', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==', 'sandra.mery@homecomputing.fr');

insert into payment (done) values (false);
insert into payment (done) values (true);

insert into expense (person_id, price, shop) values (1, 10, 'Leroy merlin');
insert into expense (person_id, price, shop, payment_id) values (2, 10, 'Dia', 1);
insert into expense (person_id, price, shop, payment_id) values (1, 10, 'Leclerc', 1);
insert into expense (person_id, price, shop, payment_id) values (2, 10, 'Agri44', 2);
insert into expense (person_id, price, shop, payment_id) values (1, 10, 'Auchan', 2);
