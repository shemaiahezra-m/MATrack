--
-- PostgreSQL database dump
--

\restrict o47GYca9vBnAgEYgJol2XzinGxc8KRKjxeeE6kEJ6nYjmpAFcFt8WgQ984Ubgt2

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

--
-- Data for Name: borrowers; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.borrowers VALUES ('BOR-001', 'Juan Dela Cruz', '09171234567', 'Design Committee');
INSERT INTO public.borrowers VALUES ('BOR-002', 'Maria Santos', '09987654321', 'Logistics Committee');
INSERT INTO public.borrowers VALUES ('BOR-003', 'Alex Reyes', '09123456789', 'Events Committee');
INSERT INTO public.borrowers VALUES ('BOR-004', 'Sofia Garcia', '09221234567', 'Multimedia Committee');


--
-- Data for Name: materials; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.materials VALUES ('MAT-001', 'Cartolina', 'Paper', 'sheets', 3, NULL, 'Red');
INSERT INTO public.materials VALUES ('MAT-002', 'Scissors', 'Tools', 'pcs', 10, 'Used for cutting paper and craft materials', NULL);
INSERT INTO public.materials VALUES ('MAT-003', 'Double-sided tape', 'Adhesive', 'rolls', 5, 'Used for mounting and attaching materials', NULL);
INSERT INTO public.materials VALUES ('MAT-004', 'Marker', 'Writing Supplies', 'pcs', 8, 'Used for labeling and writing', 'Black');
INSERT INTO public.materials VALUES ('MAT-005', 'Glue', 'Adhesive', 'bottles', 5, 'Used for paper and craft projects', NULL);
INSERT INTO public.materials VALUES ('MAT-006', 'Manila paper', 'Paper', 'pcs', 12, 'Used for posters and presentation materials', NULL);
INSERT INTO public.materials VALUES ('MAT-007', 'Masking tape', 'Adhesive', 'rolls', 6, 'Used for temporary mounting and labeling', NULL);
INSERT INTO public.materials VALUES ('MAT-008', 'Ruler', 'Tools', 'pcs', 5, 'Used for measuring and drawing straight lines', NULL);
INSERT INTO public.materials VALUES ('MAT-009', 'Bond Paper', 'Paper', 'Rim', 1, NULL, 'White');


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.transactions VALUES ('TRX-001', 'MAT-001', 'BOR-001', 'BORROWED', 3, '2026-09-28 02:30:50.095128', '2026-09-30', NULL, 'ACTIVE', 'Materials for event decorations');
INSERT INTO public.transactions VALUES ('TRX-002', 'MAT-002', 'BOR-002', 'BORROWED', 2, '2026-09-28 02:30:50.095128', '2026-10-01', NULL, 'ACTIVE', 'For committee event preparation');
INSERT INTO public.transactions VALUES ('TRX-003', 'MAT-003', 'BOR-003', 'USED', 1, '2026-09-28 02:30:50.095128', NULL, NULL, 'COMPLETED', 'Used for poster mounting');
INSERT INTO public.transactions VALUES ('TRX-004', 'MAT-004', 'BOR-004', 'BORROWED', 2, '2026-09-28 02:30:50.095128', '2026-09-29', NULL, 'ACTIVE', 'For event signage');
INSERT INTO public.transactions VALUES ('TRX-005', 'MAT-005', 'BOR-001', 'RETURNED', 1, '2026-09-28 02:30:50.095128', NULL, '2026-09-27 15:30:00', 'COMPLETED', 'Returned after use');
INSERT INTO public.transactions VALUES ('TRX-006', 'MAT-006', 'BOR-002', 'DISPOSED', 1, '2026-09-28 02:30:50.095128', NULL, NULL, 'COMPLETED', 'Damaged during use');


--
-- PostgreSQL database dump complete
--

\unrestrict o47GYca9vBnAgEYgJol2XzinGxc8KRKjxeeE6kEJ6nYjmpAFcFt8WgQ984Ubgt2

