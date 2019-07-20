SELECT id as project_id, res_name into outfile "~/project.csv" 
	FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
	LINES TERMINATED BY '\n'
	FROM project;


SELECT session_id, 
	project_id, 
	project.res_name as project_name,
	qd.quest_id, 
	session.user_id, 
	user.status as user_status,
	question.name as q_name,
	question_id as q_id,
	`order`,
	dv,
	starttime, endtime
	INTO OUTFILE '~/psa_quest.csv' 
	FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
	LINES TERMINATED BY '\n'
	FROM session 
	LEFT JOIN user USING (user_id)
	LEFT JOIN project ON project.id = project_id
	LEFT JOIN quest_data AS qd ON qd.session_id = session.id
	LEFT JOIN question ON qd.quest_id = question.quest_id AND question_id = question.id
	WHERE user.status IN ("guest", "registered");
	
SELECT session_id, 
	project_id, 
	project.res_name as project_name,
	exp_id, 
	session.user_id, 
	user.status as user_status,
    trial.name as trial_name,
    trial_n,
    `order`,
    dv,
    rt,
    side,
    ed.dt
    INTO OUTFILE '~/psa_exp.csv' 
	FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
	LINES TERMINATED BY '\n'
    FROM session 
    LEFT JOIN user USING (user_id)
    LEFT JOIN project ON project.id = project_id
    LEFT JOIN exp_data AS ed ON ed.session_id = session.id
    LEFT JOIN trial USING (exp_id, trial_n);