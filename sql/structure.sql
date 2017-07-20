create table transaction
(
    id serial not null
        constraint transaction_pkey
        primary key,
    txid varchar(255),
    data jsonb,
    updated_at timestamp default now() not null
)
;