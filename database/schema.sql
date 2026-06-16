-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS content_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE content_service;


create table content_service.authors
(
    id           int auto_increment
        primary key,
    name         varchar(100)                       not null,
    email        varchar(255)                       not null,
    date_created datetime default CURRENT_TIMESTAMP not null
);

create table content_service.content_type
(
    id   int
        primary key,
    type varchar(20) not null
);

create table content_service.contractors
(
    id    int auto_increment
        primary key,
    name  varchar(100) not null,
    email varchar(255) not null
);

create table content_service.industry
(
    id       int auto_increment
        primary key,
    industry varchar(100) not null
);

create table content_service.content
(
    id             int auto_increment
        primary key,
    type_id        int                                not null,
    author_id      int                                not null,
    body           mediumtext                         not null,
    industry_id    int                                null,
    contractor_id  int                                null,
    min_cost       decimal(10, 2)                     null,
    max_cost       decimal(10, 2)                     null,
    date_published datetime                           null,
    date_edited    datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
    date_deleted   datetime                           null,
    slug           varchar(225)                       not null,
    title          varchar(200)                       not null,
    date_created   datetime default CURRENT_TIMESTAMP not null,
    constraint content_authors_id_fk
        foreign key (author_id) references content_service.authors (id),
    constraint content_content_type_id_fk
        foreign key (type_id) references content_service.content_type (id),
    constraint content_contractors_id_fk
        foreign key (contractor_id) references content_service.contractors (id),
    constraint content_industry_id_fk
        foreign key (industry_id) references content_service.industry (id)
);

create table content_service.slug_history
(
    id         int auto_increment
        primary key,
    type_id    int          not null,
    content_id int          not null,
    slug       varchar(255) not null,
    constraint slug_history_type_id_slug_uindex
        unique (type_id, slug),
    constraint slug_history_content_id_fk
        foreign key (content_id) references content_service.content (id),
    constraint slug_history_content_type_id_fk
        foreign key (type_id) references content_service.content_type (id)
);

insert into content_type (id, type) values(1, 'blog_post');
insert into content_type (id, type) values(2, 'article');
insert into content_type (id, type) values(3, 'experience');
insert into content_type (id, type) values(4, 'cost_guide');
