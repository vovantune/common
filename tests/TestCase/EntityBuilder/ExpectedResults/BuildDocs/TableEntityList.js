/**
 * @typedef {Object} TestTableOneEntity description govno
 * @property {int} id comment1
 * @property {int} col_enum
 * @property {string} col_time = 'CURRENT_TIMESTAMP' asdasd
 * @property {string} oldField
 * @property {string} notExists
 */

/**
 * @typedef {Object} TestTableTwoEntity description qweqwe
 * @property {int} id
 * @property {int} table_one_fk blabla
 * @property {string} col_text = NULL
 * @property {TestTableOneEntity} TestTableOne table_one_fk => id
 * @property {string} virtualField
 */

