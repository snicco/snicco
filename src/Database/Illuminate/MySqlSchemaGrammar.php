<?php

namespace Snicco\Database\Illuminate;

use Illuminate\Database\Schema\Grammars\MySqlGrammar as IlluminateSchemaGrammar;

class MySqlSchemaGrammar extends IlluminateSchemaGrammar
{
    
    public function compileGetTableCollation() :string
    {
        
        return "show table status where name like ?";
        
    }
    
    public function compileGetFullColumnInfo() :string
    {
        
        return "show full columns from ?";
        
    }
    
}