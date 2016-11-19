<?php
namespace Esker;
/*
* Esker ODQuery_QueryRequest Object.
* 
* @access public
*/   
class ODQuery_QueryRequest {
					
	var $filter;
	var $sortOrder;
	var $attributes;
	var $nItems;
	var $includeSubNodes = 'false';
	var $searchInArchive = 'false';
    var $fileRefMode = 'MODE_INLINED';
}