-- delete db
drop database bai3;
drop table passwords;
drop table users;
drop table unregistered_users;
drop table allowed_messages;
drop table messages;

-- create db
create database bai3;
use bai3;

create table users (
	user_id int not null primary key auto_increment,
	username varchar(30) not null,
	password_hash varchar(34) not null,
	salt varchar(10) not null,
	last_login timestamp default 0,
	last_bad_login timestamp default 0,
	login_attempts int default 0,
	unlock_login_time timestamp default 0,
	is_blocked boolean default 0,
	block_after int default 0,
	login_attempts_block int default 0,
	ret_question varchar(50),
	ret_answer varchar(50)
);

create table passwords (
	password_id int not null primary key auto_increment,
	user_id int not null,
	partial_password_hash varchar(24) not null,
	number_of_chars int not null,
	mask varchar(24) not null,
	last_used int not null default 0,
	is_used int not null default 0
);

create table unregistered_users (
	user_id int not null primary key auto_increment,
	username varchar(30) not null,
	last_bad_login timestamp default 0,
	login_attempts int default 0,
	unlock_login_time timestamp default 0,
	is_blocked boolean default 0,
	block_after int default 7,
	login_attempts_block int default 0,
	ret_question varchar(50),
	ret_answer varchar(50)
);

create table messages (
	message_id int not null primary key auto_increment,
	text varchar(100) not null,
	owner int not null,
	modified timestamp not null default current_timestamp on update current_timestamp,
	foreign key (owner) references users(user_id)
);

create table allowed_messages (
	user_id int not null,
	message_id int not null,
	foreign key (user_id) references users(user_id),
	foreign key (message_id) references messages(message_id)
);

-- populate db


