<?php
/**
 * DeleteStatementBuilder.php
 *
 * Builds the DELETE statement
 *
 * PHP version 5
 *
 * LICENSE:
 * Copyright (c) 2010-2014 Justin Swanhart and André Rothe
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author    André Rothe <andre.rothe@phosco.info>
 * @copyright 2010-2014 Justin Swanhart and André Rothe
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id: DeleteStatementBuilder.php 830 2013-12-18 09:35:42Z phosco@gmx.de $
 * 
 */

require_once dirname(__FILE__) . '/WhereBuilder.php';
require_once dirname(__FILE__) . '/FromBuilder.php';
require_once dirname(__FILE__) . '/DeleteBuilder.php';

/**
 * This class implements the builder for the whole Delete statement. You can overwrite
 * all functions to achieve another handling.
 *
 * @author  André Rothe <andre.rothe@phosco.info>
 * @license http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 *  
 */
class DeleteStatementBuilder {

    protected function buildWHERE($parsed) {
        $builder = new WhereBuilder();
        return $builder->build($parsed);
    }

    protected function buildFROM($parsed) {
        $builder = new FromBuilder();
        return $builder->build($parsed);
    }

    protected function buildDELETE($parsed) {
        $builder = new DeleteBuilder();
        return $builder->build($parsed);
    }
    
    public function processDeleteStatement($parsed) {
        $sql = $this->buildDELETE($parsed['DELETE']);// . " " . $this->buildFROM($parsed['FROM']);
        
        if (isset($parsed['WHERE'])) {
            $sql .= " " . $this->buildWHERE($parsed['WHERE']);
        }
        return $sql;
    }

    public function build($parsed) {
        // TODO: are there more than one tables possible (like [INSERT][1])
        return $this->processDeleteStatement($parsed);
        // TODO: subquery?
    }
    
}
?>
