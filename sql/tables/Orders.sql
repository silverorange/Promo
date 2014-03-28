alter table Orders add promotion_title varchar(255);
alter table Orders add promotion_total numeric(11, 2) not null default 0;
alter table Orders add promotion_code varchar(20);
