CREATE TABLE pages (
	hubspot_utmsource VARCHAR(255) DEFAULT '' NOT NULL,
	hubspot_utmmedium VARCHAR(255) DEFAULT '' NOT NULL,
	hubspot_utmcampaign VARCHAR(255) DEFAULT '' NOT NULL,
	hubspot_utmcampaign_fulllink TEXT,
);

CREATE TABLE tt_content (
	hubspot_guid VARCHAR(36) DEFAULT '' NOT NULL,
	hubspot_cta int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE fe_users (
	hubspot_id int(11) unsigned DEFAULT '0' NOT NULL,
	hubspot_created_timestamp int(11) unsigned DEFAULT '0' NOT NULL,
	hubspot_sync_timestamp int(11) unsigned DEFAULT '0' NOT NULL,
	hubspot_sync_pass int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Table structure for table 'tx_hubspot_cta'
#
CREATE TABLE tx_hubspot_cta (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	name varchar(255) DEFAULT '' NOT NULL,
	hubspot_cta_code text,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob,

	PRIMARY KEY (uid),
	KEY language (l10n_parent,sys_language_uid)
);
