<?php
/*
list of stored-procedures: subversion-ing purpose

* SAMPLE * 

DELIMITER $$
CREATE PROCEDURE test1(sid INT, sname VARCHAR(100), addr1 VARCHAR(100))
SQL SECURITY INVOKER
BEGIN
SET @qry= '';
SET @sid = sid;
SET @sname = sname;
SET @addr1 = addr1;

IF (sid IS NOT NULL) THEN
	SET @qry = CONCAT(@qry, ' AND id=? ');
ELSE
	SET @qry = CONCAT(@qry, ' AND 1=? ');
  SET @sid = 1;
END IF;
IF (sname IS NOT NULL) THEN
	SET @qry = CONCAT(@qry, ' AND store_name=\'', sname, '\'');
ELSE
	SET @qry = CONCAT(@qry, ' AND 1=? ');
  SET @sname = 1;
END IF;
IF (addr1 IS NOT NULL) THEN
	SET @qry = CONCAT(@qry, ' AND addr1=\'', addr1, '\'');
ELSE
	SET @qry = CONCAT(@qry, ' AND 1=? ');
  SET @addr1 = 1;
END IF;

SET @qry = CONCAT('SELECT id, store_name FROM stores WHERE 1 ', @qry);

PREPARE stmt FROM @qry;
EXECUTE stmt USING @sid, @sname, @addr1;
DEALLOCATE PREPARE stmt;

SELECT LAST_INSERT_ID();

END$$
DELIMITER ;





// ******************************************************** login (for both system admin and users) ********************************************************

// ******************************************************** DEFINER PROCEDURES ********************************************************

*************** preapp ***************
* preapp: sp_pre_checkLogin
p_userId INT

SELECT pos.pos_lv,  r.ip_addr, r.login_token, r.login_mod, u.active,
    IF(a.id >0, a.id, 0) AS agent_id,
    IF(up.action1 IS NULL, pos.action1, up.action1) AS perm_action1
  FROM rec_user_login r
    LEFT JOIN login_users u ON r.user_id =u.id
      LEFT JOIN user_positions pos ON u.access_lv =pos.pos_lv
      LEFT JOIN relation_agent_user au ON r.user_id =au.user_id
        LEFT JOIN agents a ON au.agent_id =a.id AND a.active >0
      LEFT JOIN user_permissions up ON u.id =up.user_id
  WHERE r.user_id =p_userId AND u.active >0 AND pos.id >0
    LIMIT 1;

*****
* preapp: sp_pre_updateActivity
p_userId INT

UPDATE rec_user_login  SET date_act =NOW()  WHERE user_id =p_userId;

*************** END: preapp ***************


*************** user related ***************
LoginController@login: sp_user_loginUser
p_userId INT, p_ip VARCHAR(50), p_token VARCHAR(100)

INSERT INTO rec_user_login (user_id, ip_addr, login_token) VALUES (p_userId, p_ip, p_token)
  ON DUPLICATE KEY UPDATE ip_addr =p_ip, login_token =p_token, date_login =NOW(), date_act =NOW(), login_mod =0;

*****
* UserController@delete: sp_user_delUser
p_userId INT, p_targetId INT

SELECT 1, fname, lname  INTO @foundUser, @modFname, @modLname
  FROM login_users WHERE id =p_userId;

SELECT 1, created_at, email, access_lv, fname, lname, active
    INTO @foundTarget,  @v_dRec, @v_email, @v_lv, @v_fname, @v_lname, @v_active
  FROM login_users WHERE id =p_targetId;

IF (@foundUser =1 AND @foundTarget =1) THEN
  INSERT INTO del_login_users (
    id, date_rec, mod_id, mod_user, email, access_lv, fname, lname, active
  ) VALUES (
    p_targetId, @v_dRec, p_userId, TRIM(CONCAT(@modFname,' ',@modLname)), @v_email, @v_lv, @v_fname, @v_lname, @v_active
  );
  DELETE FROM login_users WHERE id =p_targetId;
END IF;
  
*************** END: user ***************


*************** service provider related ***************
* ProviderController@delete: sp_provider_delProvider
p_userId INT, p_targetId INT

SELECT 1, fname, lname  INTO @foundUser, @modFname, @modLname
  FROM login_users WHERE id =p_userId;

SELECT 1, date_rec, agent_id, name, active
    INTO @foundTarget,  @v_dRec, @v_agentId, @v_name, @v_active
  FROM providers WHERE id =p_targetId;

IF (@foundUser =1 AND @foundTarget =1) THEN
  INSERT INTO del_providers (
    id, date_rec, mod_id, mod_user, agent_id, name, active
  ) VALUES (
    p_targetId, @v_dRec, p_userId, TRIM(CONCAT(@modFname,' ',@modLname)), @v_agentId, @v_name, @v_active
  );
  DELETE FROM providers WHERE id =p_targetId;
END IF;

*****
* ProviderController@createService: sp_provider_addService
p_provId INT, p_svcName VARCHAR(100), p_price DECIMAL(10,2)

SELECT agent_id INTO @agentId FROM providers WHERE id =p_provId;
IF (@agentId >0) THEN
  SELECT id INTO @svcId FROM services WHERE agent_id =@agentId AND name =p_svcName;
  IF (@svcId IS NULL) THEN
    INSERT INTO services (agent_id, name) VALUES (@agentId, p_svcName)
      ON DUPLICATE KEY UPDATE name =name;
	  SET @svcId = LAST_INSERT_ID();
  END IF;
  
  INSERT INTO provider_services (provider_id, name, price) VALUES (p_provId, p_svcName, p_price);
  SELECT LAST_INSERT_ID() AS id, @svcId AS svc_id; 
END IF;

*****
* ProviderController@updateService: sp_provider_updateService
p_provSvcId INT, p_svcName VARCHAR(100), p_price DECIMAL(10,2)

SELECT s.provider_id, p.agent_id INTO @provId, @agentId
  FROM provider_services s LEFT JOIN providers p ON s.provider_id =p.id
  WHERE s.id =p_provSvcId;

IF (@agentId >0) THEN
  SELECT id INTO @svcId FROM services WHERE agent_id =@agentId AND name =p_svcName;
  IF (@svcId IS NULL) THEN
    INSERT INTO services (agent_id, name) VALUES (@agentId, p_svcName)
      ON DUPLICATE KEY UPDATE name =name;
	  SET @svcId = LAST_INSERT_ID();
  END IF;
  
  UPDATE provider_services SET name =p_svcName, price =p_price WHERE id= p_provSvcId;
  SELECT @provId AS prov_id, @svcId AS svc_id; 
END IF;
  
*************** END: service provider ***************

// ******************************************************** END: login (for both system admin and users) ********************************************************


// ******************************************************** MASTER-AGENT PAGE (+ SYSTEM ADMIN) ********************************************************

*************** general ***************
* spa_gen_getPosLv
p_userId INT, p_token VARCHAR(50),
  OUT o_lv INT

SELECT u.access_lv INTO o_lv
  FROM login_users u
    LEFT JOIN user_positions p ON u.pos_id =p.id
    LEFT JOIN rec_user_login r ON u.id =r.user_id
WHERE u.id =p_userId AND u.active >0 AND u.access_lv >= 90 AND p.id >0
  AND r.login_token =p_token COLLATE utf8mb4_unicode_ci;

* spa_gen_validateLogin
p_userId INT, p_token VARCHAR(50),
  OUT o_lv INT, o_name VARCHAR(100)

SELECT u.access_lv, TRIM(CONCAT(u.fname,' ',u.lname)) INTO o_lv, o_name
  FROM login_users u
    LEFT JOIN user_positions p ON u.access_lv =p.pos_lv
    LEFT JOIN rec_user_login r ON u.id =r.user_id
WHERE u.id =p_userId AND u.active >0 AND p.id >0
  AND r.login_token =p_token COLLATE utf8mb4_unicode_ci;
  
*************** END: general ***************



*************** user related ***************
* Admin\AdminDataTablesController@users: spa_user_getUserList
p_userId INT, p_token VARCHAR(50),
  p_lv_masterRegion INT, p_lv_masterUser INT, p_lv_agentAdmin INT, p_lv_agentUser INT, p_lv_highestAccess INT, p_pg_start INT, p_pg_length INT
  
CALL spa_gen_validateLogin(p_userId, p_token, @lv, @uname);
IF (@lv > p_lv_masterUser) THEN
  SELECT u.*, l.date_login, a.name AS agent, a.active AS agent_active,
      IF(u.access_lv > p_lv_agentAdmin, 1,0) AS is_master,
      SUM(IF(ma.user_id >0, 1,0)) AS n_agent
    FROM login_users u
      LEFT JOIN rec_user_login l ON u.id =l.user_id
      LEFT JOIN relation_master_agents ma ON (p_lv_masterUser <= u.access_lv OR u.access_lv <= p_lv_masterRegion) AND u.id =ma.user_id
      LEFT JOIN relation_agent_user au ON (p_lv_agentUser <= u.access_lv OR u.access_lv <= p_lv_agentAdmin) AND u.id =au.user_id
        LEFT JOIN agents a ON au.agent_id =a.id
    WHERE access_lv <= p_lv_highestAccess
      GROUP BY u.id
    ORDER BY u.active DESC, is_master DESC, a.active DESC, a.id DESC, u.access_lv DESC, u.email ASC, u.id DESC
      LIMIT p_pg_start, p_pg_length;
END IF;

*****
* Admin\AdminUserController@update: spa_user_delAgent
p_userId INT, p_token VARCHAR(100),
  p_targetId INT

CALL spa_gen_getPosLv(p_userId, p_token, @lv);
IF (@lv IS NOT NULL) THEN
  DELETE FROM relation_agent_user WHERE user_id =p_targetId;
END IF;

*****
* Admin\AdminUserController@updateAgent: spa_user_updateAgent
p_userId INT, p_token VARCHAR(100),
  p_targetId INT, p_agentId INT

CALL spa_gen_getPosLv(p_userId, p_token, @lv);
IF (@lv >= 90) THEN
  IF (p_agentId > 0) THEN
    INSERT INTO relation_agent_user (user_id, agent_id) VALUES (p_targetId, p_agentId)
      ON DUPLICATE KEY UPDATE agent_id =p_agentId;
  ELSE
    DELETE FROM relation_agent_user WHERE user_id =p_targetId;
  END IF;

  SELECT fname, lname INTO @userFname, @userLname FROM login_users WHERE id =p_userId;
  UPDATE login_users SET mod_id =p_userId, mod_user =TRIM(CONCAT(@userFname,' ',@userLname)) WHERE id =p_targetId;
END IF;

*****
* Admin\AdminUserController@assignAgent: spa_user_resetAgent
p_userId INT, p_token VARCHAR(100),
  p_targetId INT

CALL spa_gen_validateLogin(p_userId, p_token, @lv, @uname);
IF (@lv IS NOT NULL) THEN
  DELETE ma1
    FROM relation_master_agents ma1 LEFT JOIN relation_master_agents ma2 ON ma1.agent_id =ma2.agent_id
    WHERE ma1.user_id <> ma2.user_id AND ma1.user_id =p_targetId AND ma2.user_id =p_userId;
END IF;

*************** END: user ***************


*************** service provider related ***************
* Admin\AdminProviderController@deleteContact: spa_provider_delContact
p_targetId INT

DELETE FROM provider_contacts WHERE id =p_targetId;
  
*************** END: service provider ***************


*************** agent related ***************
* Admin\AdminAgentController@delete: spa_agent_delAgent
p_userId INT, p_targetId INT

SELECT 1, fname, lname  INTO @foundUser, @modFname, @modLname
  FROM login_users WHERE id =p_userId;

SELECT 1, date_rec, name, addr, addr2, city, state_id, zip, tel,  rate_spiff, rate_residual, active
    INTO @foundTarget,  @v_dRec, @v_name, @v_addr, @v_addr2, @v_city, @v_stateId, @v_zip, @v_tel,  @v_spiff, @v_resid,  @v_active
  FROM agents WHERE id =p_targetId;

IF (@foundUser =1 AND @foundTarget =1) THEN
  INSERT INTO del_agents (
    id, date_rec, mod_id, mod_user, name, addr, addr2, city, state_id, zip, tel, rate_spiff, rate_residual, active
  ) VALUES (
    p_targetId, @v_dRec, p_userId, TRIM(CONCAT(@modFname,' ',@modLname)), @v_name, @v_addr, @v_addr2, @v_city, @v_stateId, @v_zip, @v_tel, @v_spiff, @v_resid, @v_active
  );
  DELETE FROM agents WHERE id =p_targetId;
END IF;

*************** END: agent ***************


*************** predefined-service related ***************
* Master\MasterServiceController@create: spa_svc_delService
p_userId INT, p_targetId INT

SELECT 1, fname, lname  INTO @foundUser, @modFname, @modLname
  FROM login_users WHERE id =p_userId;

IF (@foundUser =1) THEN
  DELETE FROM services WHERE id =p_targetId;
END IF;
  
*************** END: predefined-service ***************

// ******************************************************** END: MASTER-AGENT PAGE ********************************************************



// ******************************************************** AGENT PAGE ********************************************************


*************** customer ***************
* CustomerController@delete: sp_cust_delCustomer
p_authId INT, p_targetId INT

SELECT 1, TRIM(CONCAT(fname,' ',lname))  INTO @foundUser, @authName
  FROM login_users WHERE id =p_authId;

SELECT 1, date_rec, agent_id, name, tel,  addr, addr2, city, state_id, zip,  tax_id, email
    INTO @foundTarget,  @v_dRec, @v_agentId, @v_name, @v_tel,  @v_addr, @v_addr2, @v_city, @v_stateId, @v_zip,  @v_taxId, @v_email
  FROM customers WHERE id =p_targetId;

IF (@foundUser =1 AND @foundTarget =1) THEN
  INSERT INTO del_customers (
    id, date_rec, mod_id, mod_user, agent_id, name, tel,  addr, addr2, city, state_id, zip, tax_id, email
  ) VALUES (
    p_targetId, @v_dRec, p_authId, @authName,  @v_agentId, @v_name, @v_tel,  @v_addr, @v_addr2, @v_city, @v_stateId, @v_zip,  @v_taxId, @v_email
  );
  DELETE FROM customers WHERE id =p_targetId;
END IF;

*************** END: customer ***************


// ******************************************************** END: AGENT PAGE ********************************************************





// ******************************************************** INVOKER PROCEDURES ********************************************************
SQL SECURITY INVOKER

***** config *****
* user-login: spi_user_getCpanelLoginList

SELECT r.user_id, r.login_key, r.time_login, r.time_activity, r.ip_addr,  u.access_lv, u.login_name, u.fname, u.lname, u.active, u.deleted
  FROM comm_login r
    LEFT JOIN comm_users u ON r.user_id=u.id
  WHERE 1
  ORDER BY r.time_activity DESC, r.time_login DESC;

***** END: config *****









*/
require_once('configure.php');


// ***** use 'mysql_storedproc' user to create and manage procedures
$mysqli = mysqli_connect(DB_HOST, 'mysql_storedproc', 'sproc@INlogic1', DB_NAME);


$qry = 
"CREATE PROCEDURE sp_pre_updateActivity (
p_userId INT
)
BEGIN

UPDATE rec_user_login  SET date_act =NOW()  WHERE user_id =p_userId;
  
END";
/*
SQL SECURITY INVOKER





PREPARE stmt FROM @q;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
  
*/

if (($res = $mysqli->query($qry)) ===FALSE) 
  die('fail prep: '.$mysqli->error);


die('success on: '.$qry);
?>