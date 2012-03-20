<?php

	class SortQueryArgument extends QueryArgument{
            function toString(){
                $arg = sprintf("\n" . '${\'%s_argument\'} = new SortArgument(\'%s\', %s);' . "\n"
                                        , $this->argument_name
                                        , $this->argument_name
                                        , '$args->'.$this->variable_name);


                $arg .= $this->argument_validator->toString();

                $arg .= sprintf('if(!${\'%s_argument\'}->isValid()) return ${\'%s_argument\'}->getErrorMessage();' . "\n"
                                    , $this->argument_name
                                    , $this->argument_name
                                    );
                return $arg;
            }
        }
?>
