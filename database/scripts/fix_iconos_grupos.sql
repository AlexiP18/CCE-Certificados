-- Arreglar iconos de grupos que se guardaron como texto
UPDATE grupos 
SET icono = CASE icono 
    WHEN 'workshop' THEN '🛠️'
    WHEN 'course' THEN '📚'
    WHEN 'trophy' THEN '🏆'
    WHEN 'seminar' THEN '🎓'
    WHEN 'award' THEN '🏅'
    WHEN 'certificate' THEN '📜'
    WHEN '??' THEN '📚'
    ELSE icono 
END
WHERE icono IN ('workshop', 'course', 'trophy', 'seminar', 'award', 'certificate', '??');

-- Mostrar los grupos actualizados
SELECT id, nombre, icono FROM grupos;
