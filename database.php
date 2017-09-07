<?php
    // Hide PHP Warnings
    error_reporting(E_ERROR | E_PARSE);

    class Database {

        // MySQL Database connection detail details
        private $host = "";
        private $user = "";
        private $pass = "";
        private $name = "";

        // Global function variables
        private $status = false;
        private $connect;
        private $result = array();
        private $row_count;

        public function connect() {
            if (!$this->status) {
                $this->connect = new mysqli($this->host, $this->user, $this->pass, $this->name);
                if ($this->connect->connect_errno > 0) {
                    array_push($this->result, $this->connect->connect_error);
                    return false; // Connection encounted issue whilst selecting database
                } else {
                    $this->status = true;
                    return true; // Connection has been made
                }
            } else {
                return true; // Connection has already been made
            }
        }

        public function disconnect() {
            // If connection exists attempt to close
            if ($this->status) {
                if ($this->connect->close()) {
                    $this->status = false;
                    return true; // Connection closed
                } else {
                    return false; // Connection could not be closed
                }
            }
        }

        public function query($sql) {
            $query = $this->connect->query($sql);
            if ($query) {
                $this->row_count = $query->num_rows;
                for($i = 0; $i < $this->row_count; $i++) {
                    $r = $query->fetch_array();
                    $key = array_keys($r);
                    for($x = 0; $x < count($key); $x++) {
                        if (!is_int($key[$x])) {
                            if ($query->num_rows >= 1) {
                                $this->result[$i][$key[$x]] = $r[$key[$x]];
                            } else {
                                $this->result = null;
                            }
                        }
                    }
                }
                return true;
            } else {
                array_push($this->result,$this->connect->error);
                return false;
            }
        }

        public function result() {
            $value = $this->result;
            $this->result = array();
            return $value;
        }

        public function count() {
            $value = $this->row_count;
            $this->row_count = array();
            return $value;
        }

        public function escape($value) {
            return $this->connect->real_escape_string($value);
        }

        public function select($table, $rows = "*", $join = null, $where = null, $order = null, $limit = null) {
            $sql = "SELECT $rows FROM $table";
            if ($join != null) {
                $sql .= " JOIN $join";
            }
            if ($where != null) {
                $sql .= " WHERE $where";
            }
            if ($order != null) {
                $sql .= " ORDER BY $order";
            }
            if ($limit != null) {
                $sql .= " LIMIT $limit";
            }
            if ($this->exists($table)) {
                $query = $this->connect->query($sql);
                if ($query) {
                    $this->row_count = $query->num_rows;
                    for ($i = 0; $i < $this->row_count; $i++) {
                        $r = $query->fetch_array();
                        $key = array_keys($r);
                        for ($x = 0; $x < count($key); $x++) {
                            if (!is_int($key[$x])) {
                                if ($query->num_rows >= 1) {
                                    $this->result[$i][$key[$x]] = $r[$key[$x]];
                                } else {
                                    $this->result[$i][$key[$x]] = null;
                                }
                            }
                        }
                    }
                    return true;
                } else {
                    array_push($this->result,$this->connect->error);
                    return false;
                }
            } else {
                return false;
            }
        }

        public function insert($table, $data = array()) {
            if ($this->exists($table)) {
                $sql = "INSERT INTO `$table` (`" . implode("`, `", array_keys($data)) . "`) VALUES ('" . implode("', '", $data) . "')";
                if ($query = $this->connect->query($sql)) {
                    array_push($this->result, $this->connect->insert_id);
                    return true;
                } else {
                    array_push($this->result, $this->connect->error);
                    return false;
                }
            } else {
                return false;
            }
        }

        public function update($table, $data = array(), $where) {
            if ($this->exists($table)) {
                $arguments = array();
                foreach ($data as $field => $value) {
                    $arguments[] = "`$field` = '$value'";
                }
                $sql = "UPDATE `$table` SET " . implode(',',$arguments) . " WHERE $where";
                if ($query = $this->connect->query($sql)) {
                    array_push($this->result, $this->connect->affected_rows);
                    return true;
                } else {
                    array_push($this->result, $this->connect->error);
                    return false;
                }
            } else {
                return false;
            }
        }

        public function delete($table, $where = null) {
            if ($this->exists($table)) {
                if ($where == null) {
                    $sql = "DELETE FROM `$table`";
                } else {
                    $sql = "DELETE FROM `$table` WHERE $where";
                }
                if ($query = $this->connect->query($sql)) {
                    array_push($this->result, $this->connect->affected_rows);
                    return true;
                } else {
                    array_push($this->result, $this->connect->error);
                    return false;
                }
            } else {
                return false;
            }
        }

        public function exists($table) {
            $tables = $this->connect->query("SHOW TABLES FROM `" . $this->name . "` LIKE '" . $table . "'");
            if ($tables) {
                if ($tables->num_rows == 1) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }
?>
