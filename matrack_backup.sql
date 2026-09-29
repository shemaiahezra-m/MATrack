--
-- PostgreSQL database dump
--

\restrict NlDol28gufgaXXwE09yIuVPrqopmmulYkX40ywQBrRhZyc4jrbuhLHjFDWscB8a

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: borrowers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.borrowers (
    borrower_id character varying(20) NOT NULL,
    borrower_name character varying(100) NOT NULL,
    contact character varying(50),
    department character varying(100)
);


ALTER TABLE public.borrowers OWNER TO postgres;

--
-- Name: materials; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.materials (
    material_id character varying(20) NOT NULL,
    material_name character varying(100) NOT NULL,
    category character varying(50),
    unit character varying(20) NOT NULL,
    stock_quantity integer DEFAULT 0 NOT NULL,
    description text,
    color character varying(30),
    CONSTRAINT materials_stock_quantity_check CHECK ((stock_quantity >= 0))
);


ALTER TABLE public.materials OWNER TO postgres;

--
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    transaction_id character varying(20) NOT NULL,
    material_id character varying(20) NOT NULL,
    borrower_id character varying(20),
    transaction_type character varying(20) NOT NULL,
    quantity integer NOT NULL,
    transaction_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    expected_return_date date,
    return_date timestamp without time zone,
    status character varying(20) DEFAULT 'ACTIVE'::character varying,
    notes text,
    CONSTRAINT transactions_quantity_check CHECK ((quantity > 0)),
    CONSTRAINT transactions_status_check CHECK (((status)::text = ANY ((ARRAY['ACTIVE'::character varying, 'COMPLETED'::character varying, 'OVERDUE'::character varying, 'CANCELLED'::character varying])::text[]))),
    CONSTRAINT transactions_transaction_type_check CHECK (((transaction_type)::text = ANY ((ARRAY['BORROWED'::character varying, 'RETURNED'::character varying, 'USED'::character varying, 'DISPOSED'::character varying])::text[])))
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- Data for Name: borrowers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.borrowers (borrower_id, borrower_name, contact, department) FROM stdin;
BOR-001	Juan Dela Cruz	09171234567	Design Committee
BOR-002	Maria Santos	09987654321	Logistics Committee
BOR-003	Alex Reyes	09123456789	Events Committee
BOR-004	Sofia Garcia	09221234567	Multimedia Committee
\.


--
-- Data for Name: materials; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.materials (material_id, material_name, category, unit, stock_quantity, description, color) FROM stdin;
MAT-001	Cartolina	Paper	sheets	3	\N	Red
MAT-002	Scissors	Tools	pcs	10	Used for cutting paper and craft materials	\N
MAT-003	Double-sided tape	Adhesive	rolls	5	Used for mounting and attaching materials	\N
MAT-004	Marker	Writing Supplies	pcs	8	Used for labeling and writing	Black
MAT-005	Glue	Adhesive	bottles	5	Used for paper and craft projects	\N
MAT-006	Manila paper	Paper	pcs	12	Used for posters and presentation materials	\N
MAT-007	Masking tape	Adhesive	rolls	6	Used for temporary mounting and labeling	\N
MAT-008	Ruler	Tools	pcs	5	Used for measuring and drawing straight lines	\N
MAT-009	Bond Paper	Paper	Rim	1	\N	White
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transactions (transaction_id, material_id, borrower_id, transaction_type, quantity, transaction_date, expected_return_date, return_date, status, notes) FROM stdin;
TRX-001	MAT-001	BOR-001	BORROWED	3	2026-09-28 02:30:50.095128	2026-09-30	\N	ACTIVE	Materials for event decorations
TRX-002	MAT-002	BOR-002	BORROWED	2	2026-09-28 02:30:50.095128	2026-10-01	\N	ACTIVE	For committee event preparation
TRX-003	MAT-003	BOR-003	USED	1	2026-09-28 02:30:50.095128	\N	\N	COMPLETED	Used for poster mounting
TRX-004	MAT-004	BOR-004	BORROWED	2	2026-09-28 02:30:50.095128	2026-09-29	\N	ACTIVE	For event signage
TRX-005	MAT-005	BOR-001	RETURNED	1	2026-09-28 02:30:50.095128	\N	2026-09-27 15:30:00	COMPLETED	Returned after use
TRX-006	MAT-006	BOR-002	DISPOSED	1	2026-09-28 02:30:50.095128	\N	\N	COMPLETED	Damaged during use
\.


--
-- Name: borrowers borrowers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.borrowers
    ADD CONSTRAINT borrowers_pkey PRIMARY KEY (borrower_id);


--
-- Name: materials materials_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.materials
    ADD CONSTRAINT materials_pkey PRIMARY KEY (material_id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (transaction_id);


--
-- Name: transactions fk_borrower; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT fk_borrower FOREIGN KEY (borrower_id) REFERENCES public.borrowers(borrower_id);


--
-- Name: transactions fk_material; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT fk_material FOREIGN KEY (material_id) REFERENCES public.materials(material_id);


--
-- PostgreSQL database dump complete
--

\unrestrict NlDol28gufgaXXwE09yIuVPrqopmmulYkX40ywQBrRhZyc4jrbuhLHjFDWscB8a

