CREATE TABLE tx_ximatypo3pagesubscription_domain_model_subscription
(
	uid           int(11) NOT NULL auto_increment,
	pid           int(11) DEFAULT '0' NOT NULL,
	tstamp        int(11) DEFAULT '0' NOT NULL,
	crdate        int(11) DEFAULT '0' NOT NULL,
	fe_user       int(11) DEFAULT '0' NOT NULL,
	last_checked  int(11) DEFAULT '0' NOT NULL,
	hashes				text NOT NULL,
	hidden        tinyint(4) unsigned DEFAULT '0' NOT NULL,
	element_id		varchar(255) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid)
);

CREATE TABLE tx_ximatypo3pagesubscription_domain_model_favorite
(
	uid     int(11) NOT NULL auto_increment,
	pid     int(11) DEFAULT '0' NOT NULL,
	tstamp  int(11) DEFAULT '0' NOT NULL,
	crdate  int(11) DEFAULT '0' NOT NULL,
	fe_user int(11) DEFAULT '0' NOT NULL,
	hidden  tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid)
);

CREATE TABLE tt_content
(
	tx_ximatypo3pagesubscription_ignore_element_ids tinyint DEFAULT 0,
	tx_ximatypo3pagesubscription_filter_element_ids text DEFAULT '' NOT NULL,
);
