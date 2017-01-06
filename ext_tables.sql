CREATE TABLE pages (
	hubspot_utmsource VARCHAR(255) DEFAULT '' NOT NULL,
	hubspot_utmmedium VARCHAR(255) DEFAULT '' NOT NULL,
	hubspot_utmcampaign VARCHAR(255) DEFAULT '' NOT NULL,
	hubspot_utmcampaign_fulllink TEXT,
);

CREATE TABLE tt_content (
	hubspot_guid VARCHAR(36) DEFAULT '' NOT NULL,
);
