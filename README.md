# JOSELUISALD | AB

Esta biblioteca fornece uma camada para
executar testes AB em seus aplicativos.

O teste AB é útil quando você deseja
alterar qualquer coisa em seu aplicativo
mas deseja comparar diferentes variações
da mudança (ou seja, exibir um botão
que diz "Compre agora", "Ir para checkout" ou
"PAGUE!").

## Instalação

Esta biblioteca está disponível através do composer,
como você pode ver pelo seu
[packagist page](https://packagist.org/packages/joseluisald/ab).

```
composer require joseluisald/ab
```

## Criando e executando um teste

A criação de testes é muito simples, pois você só precisa
definir o nome do teste e as variações, com suas respectivas
probabilidade absoluta:

``` php
use joseluisald\Ab\Ab;

$homepageColorTest = new Ab('homepage_color', array(
    'blue' => 1,
    'red' => 1,
));
```

e neste ponto você pode mudar a cor do
página inicial simplesmente executando o teste e verificando
qual variação foi escolhida:

``` php
<html>
  ...
  ...
  <body style="background-color: <?php echo $homepageColorTest->getVariation(); ?>">
```

Claro, a bagunça de um código acima está aqui apenas
como um exemplo ;-)

## Manipulando vários testes

Tomado por um teste de raiva AB, você pode querer
para começar a usar testes AB para tudo:
é por isso que adicionamos um contêiner de teste onde
você pode cadastrar quantos testes quiser,
e recupere-os facilmente:

``` php
use joseluisald\Ab\Container;
use joseluisald\Ab\Ab;

// instanciar o container com um teste
$container = new Container(array(
    new Ab('homepage_color', array(
        'blue'  => 1,
        'black' => 2,
    )),
));

// adicionar outro teste
$container->add(new Ab('checkout_button_text', array(
    'Buy now'               => 4,
    'Go to checkout'        => 1,
    'Proceed to checkout'   => 1,
)));

// então você pode recuperar todos os testes
$container->getAll();

// ou um único teste, pelo seu nome
$container->get('checkout_button_text');
```

O `Container` implementa o `ArrayAccess` e
Interfaces `Contáveis`, para que você possa acessar suas
testa como se fosse um array:

``` php
$tests = $container; // Eu só faço isso para facilitar a leitura

foreach($tests as $test) {
    echo sprintf("Teste '%s' tem a variação %s", $test->getName(), $test->getVariation());
}

// se tiver tempo a perder conte os testes :)
echo count($tests);
```

## Variações

O peso das variações deve ser expresso em valores absolutos: se, por
exemplo, você tem `A: 1`, `B: 2` e `C: 1`, isso significa que o
porcentagem de picking de cada variação é de 25% (A), 50% (B) e
25%(C), pois a soma dos pesos é 4.

As variações podem ser definidas durante a construção do teste ou posteriormente:

``` php
$test = new Ab('checkout_button_text', array(
    'Buy now!'          => 1,
    'Go to checkout!'   => 1,
));

// ou você pode configurá-los depois
$test = new Ab('checkout_button_text');

$test->setVariations(array(
    'Buy now!'          => 1,
    'Go to checkout!'   => 1,
));
```

Lembre-se de definir as variações antes de executar o teste
com `getVariation`, senão uma exceção é lançada:

``` php
$test = new Ab('checkout_button_text');

$test->getVariation(); // lançará um BadMethodCallException
```

## Como apresentar as mesmas variações em várias solicitações

Digamos que você esteja executando um teste que define se
a cor de fundo do seu site deve ser preto ou branco.

Assim que um usuário acessar a página inicial, ele obterá a página branca, mas
assim que ele atualizar a página, ele pode obter o preto!

Para ser consistente com as variações, para a sessão de um usuário,
você deve armazenar um número único (semente) e passá-lo para o
testes antes de executá-los, assim você sempre terá certeza de que
usuário específico sempre obterá as mesmas variações do
testes:

``` php
$test = new Ab('homepage_color', array(
    'white' => 1,
    'black' => 1,
));

// definir a semente
$test->setSeed($_SESSION['seed_for_homepage_color_test']); // 326902637627;

$test->getVariation(); // black
```

Na próxima requisição, já que a semente não mudará,
o usuário obterá novamente a mesma variação, `black`.

Esta funcionalidade é implementada graças a
Funções `mt_rand` e `mt_srand` do PHP.

Você não deve especificar uma semente diferente para cada um de seus
testes, mas use o contêiner:

``` php
$container = new Container(new Ab('homepage_color', array(
    'black' => 1,
    'white' => 1,
)));

$container->setSeed($_SESSION['seed']); // 326902637627;);
```

A vantagem de colocar a semente no recipiente é que
você não precisa manter uma semente para cada teste executado
a sessão, você pode usar apenas uma semente global e o contêiner
atribuirá uma semente única a cada teste.

## Desabilitando testes

Às vezes, você pode querer desabilitar testes para propósitos diferentes,
por exemplo, se o agente do usuário que está visitando a página for um bot.

``` php
$test = new Ab('my_ab_test', array(
    'a' => 0,
    'b' => 1,
));

$test->disable();

$test->getVariation(); // retornará 'a'!
```

Depois de desabilitar o teste e executá-lo, **irá
sempre retorne a primeira variação**, não importa o que
suas chances são! Sim, mesmo zero...

## Parâmetros de teste

Você também pode anexar qualquer parâmetro que desejar
um teste apenas injetando-os (ou com o `set`
método):

``` php
$test = new Ab('example', array(1, 2), array(
    'par1' => 1,
    'par2' => new stdClass,
));

$test->set('par3', 'Whoooops!')
```

Para que você possa recuperá-los facilmente em outras partes do
o código:

``` php
$test->getParameters(); // retorna todos os parâmetros

$test->get('par3'); // Whoooops!

$test->get('i-dont-exist'); // NULL
```