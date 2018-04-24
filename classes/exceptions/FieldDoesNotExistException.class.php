<?php
/*
 *  This file is part of phynx.

 *  phynx is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  phynx is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007 - 2018, Furtmeier Hard- und Software - Support@Furtmeier.IT
 */
class FieldDoesNotExistException extends StorageException {

	private $field;
	private $where;
	
	function __construct($field, $where){
		$this->field = $field;
		$this->where = $where;
		
		parent::__construct();
		$_SESSION["messages"]->addMessage("The database tells me that no field named '$field' exists. Check your query in the $where.");
	}

	function getField(){
		return $this->field;
	}

	function getErrorMessage(){
		return "The field \"$this->field\" was not found by the database. ($this->where)";
	}
}
?>
