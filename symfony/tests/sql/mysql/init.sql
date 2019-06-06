-- CONVENTION : ID 1000000 => UUID : 82d97978-f153-11e8-b25d-100000000000

INSERT INTO `users` (`id`, `uuid`, `nct_id`, `cmp_id`, `username`, `username_canonical`, `email`, `email_canonical`, `enabled`, `salt`, `password`, `roles`) VALUES
  -- [API-TESTS] / IloveToBreakYourH0pes!
  -- Role : ADMIN
  ( 1000000, ordered_uuid('82d97978-f153-11e8-b25d-100000000000'), NULL, NULL, '[API-TESTS]', '[api-tests]', 'api-tests@example.com', 'api-tests@example.com', 1, '8bJxX4O2kFCJp3/NFSLQtXX9gAv71AYij5pFJ5gL0JM', '$argon2i$v=19$m=1024,t=2,p=2$MGJmQkhSZ1d4bzFtY21abQ$M1i9rppX0APNJ6hl326Unbk6rxzioUdGCroldpHPTJo', 'a:1:{i:0;s:10:\"ROLE_ADMIN\";}' )
;
