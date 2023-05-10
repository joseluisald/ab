<?php

namespace Ab;

use \Ab\Ab;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * A classe usada agrupa uma coleção de testes.
 */
class Container implements ArrayAccess, Countable, IteratorAggregate
{
    protected $tests = array();
    protected $seed;
    
    /**
     * Construtor
     * 
     * @param array $tests
     * @param int $seed
     */
    public function __construct(array $tests = array(), $seed = null)
    {
        foreach ($tests as $test) {
            $this->add($test);
        }
        
        if ($seed) {
            $this->setSeed($seed);
        }
    }
    
    /**
     * Obtenha a semente a ser passada para cada teste.
     * 
     * @return int
     */
    public function getSeed()
    {
        return $this->seed;
    }

    /**
     * Define a semente a ser passada para cada teste.
     * 
     * @param int $seed
     */
    public function setSeed($seed)
    {
        $this->seed = (int) $seed;
        
        foreach($this->getAll() as $test) {            
            $test->setSeed($this->calculateTestSeed($seed, $test));
        }
    }
    
    /**
     * Método conveniente para desativar todos os testes registrados neste contêiner
      * de uma vez só.
     */
    public function disableTests()
    {
        foreach ($this->getAll() as $test) {
            $test->disable();
        }
    }
    
    /**
      * Método conveniente para executar todos os testes registrados com este contêiner em
      * uma vez.
     */
    public function runTests()
    {
        foreach ($this->getAll() as $test) {
            $test->getVariation();
        }
    }
    
    /**
     * Cria, registra e retorna um teste com os parâmetros fornecidos.
     * 
     * @param string $name
     * @param array $variations
     * @param array $parameters
     * @return Ab
     */
    public function createTest($name, array $variations = array(), array $parameters = array())
    {
        $this->add(new Ab($name, $variations, $parameters));
        
        return $this[$name];
    }
    
    /**
     *Calcula uma semente para o teste $ fornecido, misturando a semente global e uma
     * representação numérica do nome do teste.
     * 
     * @param int $globalSeed
     * @param \Ab\Ab $test
     * @return int
     */
    protected function calculateTestSeed($globalSeed, Ab $test)
    {
        $seed = hexdec(substr(md5($test->getName()), 0, 7));

        if ($seed > $globalSeed) {
            return $seed - $globalSeed;
        }
        
        return $globalSeed - $seed;
    }
    
    /**
     * Retorna todos os testes cadastrados no container.
     * 
     * @return array
     */
    public function getAll()
    {
        return $this->tests;
    }
    
    /**
     * Retorna um teste com o nome $test.
     * 
     * @param string $test
     * @return \Ab\Ab
     */
    public function get($test)
    {
        return $this[$test];
    }
    
    /**
     * Adiciona um novo $test.
     * 
     * @param \Ab\Ab $test
     */
    public function add(Ab $test)
    {
        if ($this->getSeed()) {
            $test->setSeed($this->calculateTestSeed($this->getSeed(), $test));
        }
        
        $this->tests[$test->getName()] = $test;
    }
    
    /**
     * Verifica se um teste está registrado.
     * 
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return (bool) isset($this->tests[$offset]);
    }
    
    /**
     * Retorna um teste ou nulo se o teste não foi encontrado.
     * 
     * @param string $offset
     * @return Ab\Ab|null
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->tests[$offset];
        }
        
        return null;
    }
    
    /**
     * Cancela o registro de um teste.
     * 
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->tests[$offset]);
        }
    }
    
    /**
     * Registra um teste.
     * 
     * @param string $offset
     * @param Ab\Ab  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->tests[$offset] = $value;
    }
    
    /**
     * Retorna quantos testes foram cadastrados neste container.
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->getAll());
    }
    
    /**
     * Retorna o iterador a ser usado para iterar no contêiner.
     * 
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->tests);
    }
}
