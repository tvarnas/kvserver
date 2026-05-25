#!/bin/bash

# -----------------------------------------------------------------------------
# Passes as the first (and only) parameter the name of the ${MDB_CMD} script
function f_check_prerequisites() {

	if [[ ! -e "${1}" ]]; then
		echo -e "\n[ERROR] DB command script ${1} not found.\n"
		exit 255
	fi

	NUM_INSTANCES=$(ps -elf | grep mariadbd-safe | grep -v grep | wc -l)
	if [[ $NUM_INSTANCES -eq 0 ]]; then
		echo -e "\n[ERROR] MariaDB is not running.\n"
		exit 255
	fi

	if [[ -z "${MDB_DATABASE}" ]]; then
		echo -e "\n[ERROR] The MDB_DATABASE environment variable hasn't been set.\n"
		exit 255
	fi

	cd ~/Apps/mariadb/bin
	RESULT=$(sudo ./mariadb -e "USE ${MDB_DATABASE}" 2>&1)
	if [[ $? -ne 0 ]]; then
		echo -e "\n${RESULT}\n"
		exit $?
	fi
	cd - > /dev/null 2>&1
}
# -----------------------------------------------------------------------------
# Passes as the first (and only) parameter the SQL output of a SELECT statement.
# Removes the graphical table borders from the SQL output.
function f_sanitize_one_column_db_output() {

	if [[ "${1}" == "" ]] then
		echo -n ""
		return
	fi

	echo "${1}" | grep -v "\-\-"
}
# -----------------------------------------------------------------------------

BIN_DIR='/home/devel/bin/mdb_scripts'
if [[ "${BIN_DIR}" == "" ]]; then
	BIN_DIR="."
fi
MDB_CMD="${BIN_DIR}/mdb_cmd.sh"


f_check_prerequisites "${MDB_CMD}"

ADMIN_UNAME="admin"
ADMIN_A_ID="<NOT_SET_YET>"

SVARNAS_UNAME="svarnas"
SVARNAS_A_ID="<NOT_SET_YET>"

TVARNAS_UNAME="tvarnas"
TVARNAS_A_ID="<NOT_SET_YET>"

# -----------------------------------------------------------------------------
# Due to referential integrity constraints, need to delete tables in reverse order
# -----------------------------------------------------------------------------
"${MDB_CMD}" "SET FOREIGN_KEY_CHECKS = 0"
"${MDB_CMD}" "DROP TABLE IF EXISTS kv_pair"
"${MDB_CMD}" "DROP TABLE IF EXISTS host"
"${MDB_CMD}" "DROP TABLE IF EXISTS account"
"${MDB_CMD}" "SET FOREIGN_KEY_CHECKS = 1"

# -----------------------------------------------------------------------------
# TABLE account
# -----------------------------------------------------------------------------
TABLE=account

"${MDB_CMD}" "DROP TABLE IF EXISTS ${TABLE}"

"${MDB_CMD}" "CREATE TABLE ${TABLE} ( \
a_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT, \
uname VARCHAR(16) NOT NULL \
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"

# Note: user 'admin' is always inserted first for a_id=1
"${MDB_CMD}" "INSERT INTO ${TABLE} (uname) VALUES('${ADMIN_UNAME}')"
"${MDB_CMD}" "INSERT INTO ${TABLE} (uname) VALUES('${SVARNAS_UNAME}')"
"${MDB_CMD}" "INSERT INTO ${TABLE} (uname) VALUES('${TVARNAS_UNAME}')"

RESULT=$("${MDB_CMD}" "SELECT a_id FROM ${TABLE} WHERE uname='${ADMIN_UNAME}'")
ADMIN_A_ID=$(f_sanitize_one_column_db_output "${RESULT}" | grep -v a_id)

RESULT=$("${MDB_CMD}" "SELECT a_id FROM ${TABLE} WHERE uname='${SVARNAS_UNAME}'")
SVARNAS_A_ID=$(f_sanitize_one_column_db_output "${RESULT}" | grep -v a_id)

RESULT=$("${MDB_CMD}" "SELECT a_id FROM ${TABLE} WHERE uname='${TVARNAS_UNAME}'")
TVARNAS_A_ID=$(f_sanitize_one_column_db_output "${RESULT}" | grep -v a_id)

"${MDB_CMD}" "DESCRIBE ${TABLE}"

echo "  ADMIN_A_ID is ${ADMIN_A_ID}"
echo "SVARNAS_A_ID is ${SVARNAS_A_ID}"
echo "TVARNAS_A_ID is ${TVARNAS_A_ID}"

# -----------------------------------------------------------------------------
# TABLE host
# -----------------------------------------------------------------------------
TABLE=host

"${MDB_CMD}" "DROP TABLE IF EXISTS ${TABLE}"

"${MDB_CMD}" "CREATE TABLE ${TABLE} ( \
h_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT, \
a_id INT NOT NULL, \
mac_addr VARCHAR(17) NOT NULL DEFAULT '00:00:00:00:00:00',\
description VARCHAR(64) NOT NULL DEFAULT '',\
f_writeable BOOLEAN NOT NULL DEFAULT FALSE, \
CONSTRAINT fk_host_account \
FOREIGN KEY (a_id) \
REFERENCES account(a_id) \
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"

"${MDB_CMD}" "DESCRIBE ${TABLE}"

# -----------------------------------------------------------------------------
# TABLE kv_pair
# -----------------------------------------------------------------------------
TABLE=kv_pair

"${MDB_CMD}" "DROP TABLE IF EXISTS ${TABLE}"

"${MDB_CMD}" "CREATE TABLE ${TABLE} ( \
kvp_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT, \
a_id INT NOT NULL, \
kv_group VARCHAR(64) NOT NULL,\
kv_key VARCHAR(64) NOT NULL,\
kv_value LONGTEXT NOT NULL,\
CONSTRAINT fk_kvpair_account \
FOREIGN KEY (a_id) \
REFERENCES account(a_id) \
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"

"${MDB_CMD}" "DESCRIBE ${TABLE}"

# ------------------------------------------------------------------------------
# Post-table additions
# ------------------------------------------------------------------------------
TABLE=account
echo "${TABLE} table:"
"${MDB_CMD}" "SELECT * FROM ${TABLE}"

TABLE=host

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, mac_addr, description, f_writeable) VALUES \
(${SVARNAS_A_ID}, '2c:cf:67:38:64:21', 'pidevel', TRUE)"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, mac_addr, description, f_writeable) VALUES \
(${SVARNAS_A_ID}, '2c:cf:67:d7:e3:2e', 'tvremote', FALSE)"

echo "${TABLE} table:"
"${MDB_CMD}" "SELECT * FROM ${TABLE}"


TABLE=kv_pair
KV_GROUP=acronym

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'FUBAR', 'F*cked Up Beyond All Recognition')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'SNAFU', 'Situation Normal All F*cked Up')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'BOHICA', 'Bend Over, Here It Comes Again')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'FAFO', 'F*ck Around and Find Out')"


"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${TVARNAS_A_ID}, '${KV_GROUP}', 'ASAP', 'As Soon As Possible')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${TVARNAS_A_ID}, '${KV_GROUP}', 'SCUBA', 'Self-Contained Underwater Breathing Apparatus')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${TVARNAS_A_ID}, '${KV_GROUP}', 'MASH', 'Mobile Army Surgical Hospital')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${TVARNAS_A_ID}, '${KV_GROUP}', 'VIP', 'Very Important Person')"


KV_GROUP=vocabulary
"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'Automated Teller Machine', 'Αυτόματη Ταμειακή Μηχανή')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'backbone', 'ραχοκοκαλιά')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'differential diagnosis', 'διαφορική διάγνωση')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${SVARNAS_A_ID}, '${KV_GROUP}', 'rigor mortis', 'νεκρική ακαμψία')"


KV_GROUP=ip_address
"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${TVARNAS_A_ID}, '${KV_GROUP}', 'OffsiteFiles', '107.13.76.102')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${TVARNAS_A_ID}, '${KV_GROUP}', 'tvremote', '192.168.1.72')"

"${MDB_CMD}" "INSERT INTO ${TABLE} \
(a_id, kv_group, kv_key, kv_value) VALUES \
(${TVARNAS_A_ID}, '${KV_GROUP}', 'serrespi5b', '94.67.222.61')"

echo "${TABLE} table:"
"${MDB_CMD}" "SELECT * FROM ${TABLE}"
