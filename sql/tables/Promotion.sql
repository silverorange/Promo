create table Promotion (
	id serial,

	instance integer references Instance(id) on delete cascade,

	title varchar(255),
	start_date timestamp,
	end_date timestamp,
	discount_amount numeric(11, 2),
	discount_percentage numeric (5, 2),
	maximum_quantity integer,
	public_note text,
	notes text,

	primary key (id)
);
