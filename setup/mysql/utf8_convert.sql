      SELECT CONCAT('ALTER TABLE ', table_name, ' CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;') 
INTO OUTFILE './utf8.sql'
        FROM information_schema.tables 
       WHERE table_schema='psa' 
         AND table_collation != 'utf8_unicode_ci' 
    GROUP BY table_name;
    
    
    
ALTER DATABASE psa CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;
ALTER TABLE access CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;           
ALTER TABLE buttons CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;          
ALTER TABLE countries CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;        
ALTER TABLE dashboard CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;        
ALTER TABLE downloads CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;        
ALTER TABLE exp CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;              
ALTER TABLE exp_data CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;         
ALTER TABLE lab CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;              
ALTER TABLE login CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;            
ALTER TABLE options CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;          
ALTER TABLE project CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;          
ALTER TABLE project_items CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;    
ALTER TABLE quest CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;            
ALTER TABLE question CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;         
ALTER TABLE quest_data CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;       
ALTER TABLE radiorow_options CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci; 
ALTER TABLE res CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;              
ALTER TABLE session CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;          
ALTER TABLE set_items CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;        
ALTER TABLE stimuli CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;          
ALTER TABLE supervise CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;        
ALTER TABLE trial CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;            
ALTER TABLE user CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;             
ALTER TABLE versions CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;         
ALTER TABLE xafc CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;             
ALTER TABLE yoke CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci; 