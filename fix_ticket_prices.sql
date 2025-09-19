-- Corriger les prix et quantités des ticket_types

-- Événement 1: Festival Électro Gabonais 2025
UPDATE ticket_types SET price = 12000.00, available_quantity = 2000 WHERE id = 1 AND name = 'Early Bird';
UPDATE ticket_types SET price = 35000.00, available_quantity = 500 WHERE id = 2 AND name = 'VIP';

-- Événement 2: Concert Afrobeat Live
UPDATE ticket_types SET price = 10000.00, available_quantity = 1500 WHERE id = 3 AND name = 'Standard';
UPDATE ticket_types SET price = 25000.00, available_quantity = 300 WHERE id = 4 AND name = 'VIP';

-- Événement 3: Théâtre Les Misérables
UPDATE ticket_types SET price = 12000.00, available_quantity = 200 WHERE id = 5 AND name = 'Orchestre';
UPDATE ticket_types SET price = 18000.00, available_quantity = 150 WHERE id = 6 AND name = 'Balcon';

-- Événement 4: Conférence Tech Innovation 2025
UPDATE ticket_types SET price = 25000.00, available_quantity = 500 WHERE id = 7 AND name = 'Standard';
UPDATE ticket_types SET price = 45000.00, available_quantity = 200 WHERE id = 8 AND name = 'Premium';

-- Événement 5: Festival des Arts Urbains Libreville
UPDATE ticket_types SET price = 8000.00, available_quantity = 2500 WHERE id = 9 AND name = 'Pass Journée';
UPDATE ticket_types SET price = 20000.00, available_quantity = 200 WHERE id = 10 AND name = 'Pass Artiste';

-- Événement 6: Match de Gala - Lions vs Panthères
UPDATE ticket_types SET price = 5000.00, available_quantity = 8000 WHERE id = 11 AND name = 'Tribune populaire';
UPDATE ticket_types SET price = 20000.00, available_quantity = 1000 WHERE id = 12 AND name = 'Tribune VIP';

-- Événement 7: Festival de Jazz du Golfe de Guinée
UPDATE ticket_types SET price = 15000.00, available_quantity = 400 WHERE id = 13 AND name = 'Pass 1 jour';
UPDATE ticket_types SET price = 35000.00, available_quantity = 300 WHERE id = 14 AND name = 'Pass 3 jours';
UPDATE ticket_types SET price = 75000.00, available_quantity = 100 WHERE id = 15 AND name = 'VIP 3 jours';