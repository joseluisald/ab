<?php

namespace joseluisald\Ab;

use Countable;
use InvalidArgumentException;
use BadMethodCallException;

/**
 * Classe usada para representar um teste AB.
 */
class Ab implements Countable
{
    protected $name;
    protected $variations   = array();
    protected $isEnabled    = true;
    protected $hasRun       = false;
    protected $variation;
    protected $parameters   = array();
    protected $seed;
    protected $sumVariations = 0;
    
    const ERROR_TEST_RAN_WITHOUT_VARIATIONS         = "Você está tentando executar um teste sem especificar suas variações";
    const ERROR_GET_VARIATION_BEFORE_RUNNING_TEST   = "Você deve run() o teste antes de obter sua variação";
    
    /**
      * Cria um teste com o $nome fornecido e as $variações especificadas.
      *
      * As variações devem ter valor absoluto, não percentual; por exemplo,
      * - a: 100
      * - b: 100
      *
      * significa que ambas as variações têm 50% de probabilidade.
      *
      * @param string $name
      * @param array $variations
      * @param matriz $parameters
      */
    public function __construct($name, array $variations = array(), array $parameters = array())
    {
        $this->setName($name);
        $this->setVariations($variations);
        $this->setParameters($parameters);
    }
    
    /**
     * Retorna o nome do teste.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Define o $name do teste.
     * 
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * Retorna quantas variações o teste contém.
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->variations);
    }
    
    /**
     * Retorna as variações deste teste.
     * 
     * @return array
     */
    public function getVariations()
    {
        return $this->variations;
    }

    /**
     * Define as $variações deste teste.
     * 
     * @param array $variations
     */
    public function setVariations(array $variations)
    {
        $this->validateVariations($variations);
        $this->variations = $variations;
        $this->sumVariations = array_sum($this->getVariations());
    }

    /**
     * Obtenha a soma de todas as variações de pesos.
     *
     * @return int
     */
    public function getSumVariations()
    {
        return $this->sumVariations;
    }

    /**
     * Obtém a semente para este teste.
     * 
     * @return int
     */
    public function getSeed()
    {
        return $this->seed;
    }

    /**
     * Define a semente para este teste.
     * 
     * @param int $seed
     */
    public function setSeed($seed)
    {
        if (!$this->hasRun()) {
            $this->seed = (int) $seed;
        }
    }
    
    /**
      * Desabilita o teste: isso é útil quando, por exemplo, você deseja excluir
      * este teste deve ser executado para uma solicitação específica (por exemplo, bots).
     */
    public function disable()
    {
        $this->isEnabled = false;
    }
    
    /**
     * Verifica se o teste está habilitado ou não.
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Verifica se o teste está desabilitado ou não.
     * 
     * @return bool
     */
    public function isDisabled()
    {
        return !$this->isEnabled();
    }
    
    /**
      * Retorna a variação deste teste.
      *
      * Você deve executar o teste antes de obter a variação, caso contrário, um
      * BadMethodCallException é lançada.
      * Caso o teste esteja desabilitado, sempre será retornada a primeira variação,
      * mesmo que sua ímpar seja definida como 0.
      *
     * @return string
     */
    public function getVariation()
    {        
        if (!$this->hasRun()) {
            $this->run();
        }
        
        if ($this->isDisabled()) {
            $variations = array_keys($this->getVariations());

            return array_shift($variations);
        }

        return $this->variation;
    }
    
    /**
     * Verifica se o teste foi executado ou não.
     * 
     * @param bool $ran
     * @return bool
     */
    public function hasRun($ran = null)
    {
        if (!is_null($ran)) {
            $this->hasRun = (bool) $ran;
        }
        
        return (bool) $this->hasRun;
    }
    
    /**
     * Obtém os parâmetros para este teste.
     * 
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Define os parâmetros para este teste.
     * 
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }
    
    /**
     * Retorna o parâmetro de um teste.
     * 
     * @param string $parameter
     * @return mixed
     */
    public function get($parameter)
    {
        if (isset($this->parameters[$parameter])) {
            return $this->parameters[$parameter];
        }
    }
    
    /**
     * Retorna o parâmetro de um teste.
     * 
     * @param string $parameter
     * @param mixed $value
     */
    public function set($parameter, $value)
    {
        $this->parameters[$parameter] = $value;
    }
    
    /**
     * Executa o teste.
     */
    public function run()
    {
        if (!$this->count()) {
            throw new BadMethodCallException(self::ERROR_TEST_RAN_WITHOUT_VARIATIONS);
        }
        
        $this->hasRun(true);
        $this->calculateVariation();
    }
    
    /**
      * Valida uma matriz de variações.
      * Todas as variações devem ter um valor inteiro.
      *
     * @param array $variations
     * @throws InvalidArgumentException
     */
    protected function validateVariations(array $variations)
    {
        array_walk($variations, function($variation) {
            if (!is_int($variation)) {
                throw new InvalidArgumentException;
            }
        });
    }
    
    /**
     * Calcula a variação deste teste.
     */
    protected function calculateVariation()
    {
        if ($this->getSeed()) {
            mt_srand($this->getSeed());
        }

        $sum    = 0;
        $random = mt_rand(1, $this->getSumVariations());
        
        foreach ($this->getVariations() as $variation => $odd) {
            $sum += $odd;
            
            if($random <= $sum) {
                $this->variation = $variation;
                
                return;
            }
        }
    }

}
