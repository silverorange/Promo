create table PromotionCode (
	id serial,
	promotion integer not null references Promotion(id) on delete cascade,
	code varchar(20) unique not null,
	limited_use boolean not null default true,
	createdate timestamp not null,
	used_date timestamp,
	primary key (id)
);
