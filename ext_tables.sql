CREATE TABLE tx_mdnotifications_domain_model_notification (
	record_key varchar(255) NOT NULL DEFAULT '',
	record_id int(11) NOT NULL DEFAULT '0',
	record_date int(11) NOT NULL DEFAULT '0',
	feuser int(11) NOT NULL DEFAULT '0',
	data text NOT NULL DEFAULT '',

	KEY feuser_recordkey (feuser,record_key,record_id)
);
