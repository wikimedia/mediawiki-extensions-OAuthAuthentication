CREATE TABLE /*_*/oauthauth_user (
  `oaau_rid` int(10) unsigned NOT NULL,
  `oaau_uid` int(10) unsigned NOT NULL PRIMARY KEY,
  `oaau_username` varchar(255) binary not null,
  `oaau_access_token` varchar(127) binary not null default '',
  `oaau_access_secret` varchar(127) binary not null default '',
  `oaau_identify_timestamp` binary(14) not null default '',
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/idx_rid ON /*_*/oauthauth_user (`oaau_rid`);

