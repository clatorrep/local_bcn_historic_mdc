<?php
/**
 * This function rescues the information to save the historical report of the courses entered.
 *
 * @param array $courses an array of courses to rescue the report data.
 * @return object $data moodle object with the records
 */
function get_report_records($courses){
    global $DB;

    [$insql, $inparams] = $DB->get_in_or_equal($courses);

    $sql = 
            "SELECT * FROM (
            SELECT 
                u.id AS 'userid',
                c.id AS 'courseid',
                u_modules.coursestart AS 'coursestart',
                CONCAT(IFNULL(u_modules.total, 0),
                        ' de ',
                        IFNULL(c_modules.total, 0)) AS 'progress',
                IFNULL(ROUND(IFNULL(u_modules.total, 0) * 100 / IFNULL(c_modules.total, 0),
                                2),
                        0.00) AS 'progressperc',
                u_quiz.fecha_prueba AS 'quizdate',
                CONCAT(IFNULL(u_quiz.total, 0),
                        ' de ',
                        IFNULL(c_quiz.total, 0)) AS 'progressquiz',
                IFNULL(u_quiz.promedio, 0.00) AS 'finalgrade',
                IF(c_quiz.total IS NOT NULL,
                    CASE
                        WHEN
                            (IFNULL(u_quiz.total, 0) > 0
                                AND IFNULL(u_quiz.total, 0) < c_quiz.total)
                                AND (IFNULL(u_modules.total, 0) > 0)
                        THEN
                            'Iniciado'
                        WHEN
                            (u_quiz.total = c_quiz.total)
                                AND (u_modules.total = c_modules.total)
                        THEN
                            IF(u_quiz.promedio >= 75,
                                'Aprobado',
                                'Reprobado')
                        ELSE 'Pendiente'
                    END,
                    CASE
                        WHEN
                            (IFNULL(u_modules.total, 0) > 0)
                                AND (IFNULL(u_modules.total, 0) < IFNULL(c_modules.total, 0))
                        THEN
                            'Iniciado'
                        WHEN
                            (IFNULL(u_modules.total, 0) = IFNULL(c_modules.total, 0)
                                AND IFNULL(u_modules.total, 0) <> 0)
                        THEN
                            'Aprobado'
                        ELSE 'Pendiente'
                    END) AS 'status',
                UNIX_TIMESTAMP() AS 'timecreated'
        FROM {user} AS u
        JOIN {role_assignments} AS ra ON u.id = ra.userid
        JOIN {context} AS ctx ON ra.contextid = ctx.id AND ctx.contextlevel = 50
        JOIN {course} AS c ON ctx.instanceid = c.id
        JOIN {enrol} AS e ON c.id = e.courseid
        JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND u.id = ue.userid
        LEFT JOIN
            (SELECT 
                course,
                COUNT(*) AS total
            FROM {course_modules}
            WHERE deletioninprogress <> 1
            AND completion <> 0
            AND course $insql
            GROUP BY course) AS c_modules ON c.id = c_modules.course
        LEFT JOIN
            (SELECT 
                cm.course, 
                cmc.userid, 
                COUNT(*) AS total,
                MIN(timemodified) AS coursestart
            FROM
                {course_modules} AS cm
            JOIN {course_modules_completion} AS cmc ON cm.id = cmc.coursemoduleid
            WHERE cm.deletioninprogress <> 1
                AND cm.completion <> 0
                AND cmc.completionstate <> 0
                AND cm.course $insql
            GROUP BY cm.course , cmc.userid) AS u_modules ON c.id = u_modules.course
                AND u.id = u_modules.userid
        LEFT JOIN
            (SELECT 
                cm.course, 
                COUNT(*) AS total
            FROM
                {course_modules} AS cm
            JOIN {modules} AS m ON cm.module = m.id
            WHERE
                cm.deletioninprogress <> 1
                AND cm.completion <> 0
                AND m.name = 'quiz'
                AND cm.course $insql
            GROUP BY course) AS c_quiz ON c.id = c_quiz.course
        LEFT JOIN
            (SELECT 
                gi.courseid,
                gg.userid,
                ROUND(AVG(gg.finalgrade * 100 / gi.grademax), 2) AS promedio,
                COUNT(*) AS total,
                MAX(gg.timemodified) AS fecha_prueba
            FROM
                {grade_items} AS gi
            JOIN {grade_grades} AS gg ON gi.id = gg.itemid
            WHERE
                gi.itemmodule = 'quiz'
                    AND gg.finalgrade IS NOT NULL
                    AND gi.courseid $insql
            GROUP BY gi.courseid , gg.userid) AS u_quiz ON c.id = u_quiz.courseid
                AND u.id = u_quiz.userid
        WHERE u.deleted <> 1
            AND u.icq <> 1
            AND c.id $insql) AS tb
            WHERE status LIKE '%Aprobado%' OR status LIKE '%Reprobado%'";

        $inparams = array_merge($inparams, $inparams, $inparams, $inparams, $inparams);

        $data = $DB->get_recordset_sql($sql, $inparams);
        return $data;
}

/**
 * This function saves the records in the historical report table.
 *
 * @param object $data moodle object with the records
 * @return string $message "<number of records> saved records"
 */
function save_report_records($data) {
    global $DB;

    $table = 'local_bcn_historic_mdc';

    $idinserts = array();

    foreach ($data as $item) {
        $idinserts[] = $DB->insert_record($table, $item, true, true);
    }

    return count($idinserts) . ' ' . get_string('savedrecords', 'local_bcn_historic_mdc');
}