<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
require_once(__DIR__ . '/Fixture/BasicClass.php');
require_once(__DIR__ . '/Fixture/ClassWithOptionalArguments.php');
require_once(__DIR__ . '/Fixture/SomeInterface.php');
require_once(__DIR__ . '/Fixture/SomeImplementation.php');
require_once(__DIR__ . '/Fixture/ReconstitutableClassWithSimpleProperties.php');
require_once(__DIR__ . '/Fixture/ClassWithSomeImplementationInjected.php');
require_once(__DIR__ . '/Fixture/ClassWithUnmatchedRequiredSetterDependency.php');
require_once(__DIR__ . '/Fixture/ClassWithInjectSettingsMethod.php');
require_once(__DIR__ . '/Fixture/ClassWithSetterAndPropertyInjection.php');

/**
 * Testcase for the Object Object Builder
 *
 * @version $Id: ObjectBuilderTest.php 3643 2010-01-15 14:38:07Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\Object\ObjectFactory
	 */
	protected $mockObjectFactory;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var \F3\FLOW3\Object\ObjectBuilder
	 */
	protected $objectBuilder;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$this->mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$this->mockReflectionService->expects($this->any())->method('getPropertyNamesByTag')->will($this->returnValue(array()));

		$this->objectBuilder = new \F3\FLOW3\Object\ObjectBuilder();
		$this->objectBuilder->injectObjectManager($this->mockObjectManager);
		$this->objectBuilder->injectObjectFactory($this->mockObjectFactory);
		$this->objectBuilder->injectReflectionService($this->mockReflectionService);
	}

	/**
	 * Checks if createObject does a simple setter injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSimpleExplicitSetterInjection() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setLifecycleInitializationMethodName('initializeAfterPropertiesSet');
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('firstDependency', $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT),
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('secondDependency', $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT),
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('injectOrSetMethod', 'dummy', \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);

		$this->assertSame($object->getFirstDependency(), $injectedClass, 'The class ' . $injectedClassName . ' (first dependency) has not been setter-injected although it should have been.' . get_class($object->getFirstDependency()));
	}

	/**
	 * Checks if createObject does a setter injection with straight values correctly (in this case a string)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionWithStraightValues() {
		$time = microtime();
		$someConfigurationProperty = new \F3\FLOW3\Object\Configuration\ConfigurationProperty('someProperty', $time, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\BasicClass');
		$objectConfiguration->setProperty($someConfigurationProperty);

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
		$this->assertEquals($time, $object->getSomeProperty(), 'The straight value has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a setter injection with arrays correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionWithArrays() {
		$someArray = array(
			'foo' => 'bar',
			199 => 837,
			'doo' => TRUE
		);
		$someConfigurationProperty = new \F3\FLOW3\Object\Configuration\ConfigurationProperty('someProperty', $someArray, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\BasicClass');
		$objectConfiguration->setProperty($someConfigurationProperty);

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
		$this->assertEquals($someArray, $object->getSomeProperty(), 'The array has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionViaInjectMethod() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setLifecycleInitializationMethodName('initializeAfterPropertiesSet');
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('firstDependency', $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT),
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('secondDependency', $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT),
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('injectOrSetMethod', 'dummy', \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);

		$this->assertSame($object->getSecondDependency(), $injectedClass, 'The class ' . $injectedClassName . ' (second dependency) has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Object\Exception\InvalidClassException
	 * @author Robert Lemke <robert@typo3.or>g
	 * @see http://typo3.org/go/issue/3027
	 */
	public function createObjectThrowsExceptionIfObjectImplementationIsAnInterface() {
		$interfaceName = uniqid('Test');
		eval('namespace F3\Virtual; interface ' . $interfaceName . '{}');
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\Virtual\\' . $interfaceName);
		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectMethodIsPreferredOverSetMethod() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('injectOrSetMethod', 'dummy', \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
		$this->assertEquals('inject', $object->injectOrSetMethod, 'Setter inject was done via the set* method but inject* should have been preferred!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autowiringDetectsInjectSettingsMethodAndInjectsTheSettingsOfTheObjectsPackage() {
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3')->will($this->returnValue(array('the settings')));
		$this->objectBuilder->injectConfigurationManager($mockConfigurationManager);

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithInjectSettingsMethod';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);

		$object = $this->objectBuilder->createObject($objectName, $objectConfiguration);
		$this->assertSame(array('the settings'), $object->settings);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifTheValueOfAnObjectPropertyIsOfTypeStringItSpecifiesTheObjectToInject() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' . $injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('firstDependency', $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
		$this->assertSame($object->getFirstDependency(), $injectedClass);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifTheValueOfAnObjectPropertyIsAnObjectConfigurationObjectItSpecifiesTheObjectToInjectAndOverridesItsObjectConfiguration() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' . $injectedClassName;

		$injectedObjectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('InjectedObject', $injectedClassName);

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('firstDependency', $injectedObjectConfiguration, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
		$this->assertType($injectedClassName, $object->getFirstDependency());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifTheValueOfAnObjectArgumentIsOfTypeStringItSpecifiesTheObjectToInject() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' . $injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
		$this->assertSame($object->argument1, $injectedClass);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifTheValueOfAnObjectArgumentIsAnObjectConfigurationObjectItSpecifiesTheObjectToInjectAndOverridesItsObjectConfiguration() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' . $injectedClassName;

		$injectedObjectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('InjectedObject', $injectedClassName);

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, $injectedObjectConfiguration, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
		$this->assertType($injectedClassName, $object->argument1);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theValueOfSettingsCanBeInjectedThroughArgumentsOfTypeSetting() {
		$settings = array('Bar' => array('Baz' => 'TheValue'));
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Foo')->will($this->returnValue($settings));

		$this->objectBuilder->injectConfigurationManager($mockConfigurationManager);

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, 'Foo.Bar.Baz', \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_SETTING)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
		$this->assertSame($object->argument1, 'TheValue');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theValueOfSettingsCanBeInjectedThroughPropertiesOfTypeSetting() {
		$settings = array('Bar' => array('Baz' => 'TheValue'));
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Foo')->will($this->returnValue($settings));

		$this->objectBuilder->injectConfigurationManager($mockConfigurationManager);

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('firstDependency', 'Foo.Bar.Baz', \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
		$this->assertSame('TheValue', $object->getFirstDependency());
	}

	/**
	 * Checks if createObject does a simple constructor injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSimpleConstructorInjection() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->once())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT),
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(2, 42, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(3, 'Foo Bar Skårhøj', \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);

		$injectionSucceeded = (
			$object->argument1 === $injectedClass &&
			$object->argument2 === 42 &&
			$object->argument3 === 'Foo Bar Skårhøj'
		);
		$this->assertTrue($injectionSucceeded, 'The class Injected class has not been (correctly) constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a constructor injection with arrays correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithArrays() {
		$someArray = array(
			'foo' => 'bar',
			199 => 837,
			'doo' => TRUE
		);
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, $someArray, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
		$this->assertEquals($someArray, $object->argument1, 'The array has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a constructor injection with numeric values correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithNumericValues() {
		$secondValue = 99;
		$thirdValue = 3.14159265359;
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(2, $secondValue, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(3, $thirdValue, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
		$this->assertEquals($secondValue, $object->argument2, 'The second straight numeric value has not been constructor-injected although it should have been.');
		$this->assertEquals($thirdValue, $object->argument3, 'The third straight numeric value has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a constructor injection with boolean values and objects correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithBooleanValuesAndObjects() {
		$firstValue = TRUE;
		$thirdValue = new \ArrayObject(array('foo' => 'bar'));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, $firstValue, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(3, $thirdValue, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
		$this->assertEquals($firstValue, $object->argument1, 'The first value (boolean) has not been constructor-injected although it should have been.');
		$this->assertEquals($thirdValue, $object->argument3, 'The third argument (an object) has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject can handle difficult constructor arguments (with quotes, special chars etc.)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithDifficultArguments() {
		$firstValue = "Hir hier deser d'Sonn am, fu dem Ierd d'Liewen, ze schéinste Kirmesdag hannendrun déi.";
		$secondValue = 'Oho ha halo\' maksimume, "io fari jeso naŭ plue" om backslash (\\)nea komo triliono postpostmorgaŭ.';

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, $firstValue, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(2, $secondValue, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
		$this->assertEquals($firstValue, $object->argument1, 'The first value (string with quotes) has not been constructor-injected although it should have been.');
		$this->assertEquals($secondValue, $object->argument2, 'The second value (string with double quotes and backslashes) has not been constructor-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifTheNameOfAnObjectToBeInjectedAsConstructorArgumentContainsDotsItIsConsideredToBeAPathToASettingContainingTheActualObjectName() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\Virtual\Foo')->will($this->returnValue(new \stdClass));

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3')->will($this->returnValue(array('foo' => 'F3\Virtual\Foo')));
		$this->objectBuilder->injectConfigurationManager($mockConfigurationManager);

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, 'FLOW3.foo', \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalArguments', $objectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifTheNameOfAnObjectToBeInjectedAsPropertyContainsDotsItIsConsideredToBeAPathToASettingContainingTheActualObjectName() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\Virtual\Foo')->will($this->returnValue(new \stdClass));

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3')->will($this->returnValue(array('foo' => 'F3\Virtual\Foo')));
		$this->objectBuilder->injectConfigurationManager($mockConfigurationManager);

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\BasicClass');
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('firstDependency', 'FLOW3.foo', \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createObjectCallsProxyConstructForAOPProxies() {
		$className = uniqid('SomeClass');
		eval('class ' . $className . ' {}');

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->any())->method('getClassName')->will($this->returnValue($className));
		$mockProxy = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array(), array(), '', FALSE);
		$mockProxy->expects($this->once())->method('FLOW3_AOP_Proxy_construct');

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\ObjectBuilder', array('instantiateClass', 'injectArguments', 'injectProperties', 'callLifecycleInitializationMethod'), array(), '', FALSE);
		$mockObjectBuilder->expects($this->once())->method('instantiateClass')->will($this->returnValue($mockProxy));

		$object = $mockObjectBuilder->createObject('F3\FLOW3\AOP\ProxyInterface', $mockObjectConfiguration);
	}

	/**
	 * Checks if the object builder calls the lifecycle initialization method after injecting properties
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCallsLifecycleInitializationMethodName() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . ' {}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		$objectConfiguration->setLifecycleInitializationMethodName('initializeAfterPropertiesSet');
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationProperty('firstDependency', $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT),
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
		$this->assertTrue($object->hasBeenInitialized());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectUsesACustomFactoryForInstantiatingTheObjectIfOneWasSpecifiedInTheObjectConfiguration() {
		$className = uniqid('Foo');
		eval('namespace F3\Virtual; class ' . $className . ' {}');
		$fullClassName = 'F3\Virtual\\' .$className;

		$expectedObject = new \ArrayObject();

		$mockFactory = $this->getMock('MockFactory', array('createTheThing'));
		$mockFactory->expects($this->once())
			->method('createTheThing')
			->with('Argument No. 1', 'Argument No. 2')
			->will($this->returnValue($expectedObject));

		$this->mockObjectManager->expects($this->once())->method('getObject')->with(get_class($mockFactory))->will($this->returnValue($mockFactory));

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($fullClassName);
		$objectConfiguration->setFactoryClassName(get_class($mockFactory));
		$objectConfiguration->setFactoryMethodName('createTheThing');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, 'Argument No. 1', \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(2, 'Argument No. 2', \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$actualObject = $this->objectBuilder->createObject($fullClassName, $objectConfiguration);
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * Checks if autowiring of constructor arguments for dependency injection basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForConstructorInjection() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected';
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\BasicClass')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\BasicClass));
		$this->mockReflectionService->expects($this->once())->method('getClassConstructorName')->with($objectName)->will($this->returnValue('__construct'));
		$constructorParameters = array(
			'argument1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
			),
			'argument2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\BasicClass'
			)
		);
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, '__construct')->will($this->returnValue($constructorParameters));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected');

		$object = $this->objectBuilder->createObject($objectName, $objectConfiguration);
		$this->assertType('F3\FLOW3\Tests\Object\Fixture\SomeImplementation', $object->argument1, 'Autowiring didn\'t work out for ' . $objectName);
	}

	/**
	 * Checks if autowiring doesn't override constructor arguments which have already been defined in the object configuration
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringForConstructorInjectionRespectsAlreadyDefinedArguments() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected';
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));
		$this->mockReflectionService->expects($this->once())->method('getClassConstructorName')->with($objectName)->will($this->returnValue('__construct'));
		$constructorParameters = array(
			'argument1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
			),
			'argument2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\BasicClass'
			)
		);
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, '__construct')->will($this->returnValue($constructorParameters));

		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . ' extends \F3\FLOW3\Tests\Object\Fixture\BasicClass {}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected');
		$objectConfiguration->setArguments(array(
			new \F3\FLOW3\Object\Configuration\ConfigurationArgument(2, $injectedClassName, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT)
		));

		$object = $this->objectBuilder->createObject($objectName, $objectConfiguration);
		$this->assertSame($object->argument2, $injectedClass, 'Autowiring didn\'t respect that the second constructor argument was already set in the object configuration!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForSetterInjectionViaInjectMethod() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected';
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\BasicClass')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\BasicClass));
		$this->mockReflectionService->expects($this->once())->method('getClassConstructorName')->with($objectName)->will($this->returnValue('__construct'));
		$constructorParameters = array(
			'argument1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
			),
			'argument2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\BasicClass'
			)
		);
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, '__construct')->will($this->returnValue($constructorParameters));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);

		$setterParameters = array(array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
		));
		$this->mockReflectionService->expects($this->at(2))->method('getMethodParameters')->with($objectName, 'injectOptionalSetterArgument')->will($this->returnValue($setterParameters));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));

		$object = $this->objectBuilder->createObject($objectName, $objectConfiguration);
		$this->assertType('F3\FLOW3\Tests\Object\Fixture\SomeImplementation', $object->optionalSetterArgument , 'Autowiring didn\'t work for the optional setter injection via the inject*() method.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception
	 */
	public function autoWiringThrowsExceptionForUnmatchedDependenciesOfRequiredSetterInjectedDependencies() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('stdClass')->will($this->throwException(new \F3\FLOW3\Object\Exception()));
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithUnmatchedRequiredSetterDependency';
		$setterParameters = array(array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'stdClass'
		));
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, 'injectRequiredSetterArgument')->will($this->returnValue($setterParameters));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);

		$this->objectBuilder->createObject($objectName, $objectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autowirePropertiesSetsInjectionPropertyForPropertiesAnnotatedWithInject() {
		$className = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSetterAndPropertyInjection';

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with($className, 'injectFirstDependency')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->once())->method('getPropertyNamesByTag')->with($className, 'inject')->will($this->returnValue(array('secondDependency')));
		$mockReflectionService->expects($this->once())->method('getPropertyTagValues')->with($className, 'secondDependency', 'var')->will($this->returnValue(array('F3\Coffee\Bar')));

		$mockObjectBuilder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\ObjectBuilder'), array('dummy'), array(), '', FALSE);
		$mockObjectBuilder->injectReflectionService($mockReflectionService);
		$actualProperties = $mockObjectBuilder->_call('autowireProperties', array(), $className);

		$this->assertSame('F3\Coffee\Bar', $actualProperties['secondDependency']->getValue());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setterInjectionIsPreferredOverPropertyInjection() {
		$className = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSetterAndPropertyInjection';

		$methodParameters = array('firstDependency' => array('class' => 'F3\FLOW3\Object\ObjectManagerInterface'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with($className, 'injectFirstDependency')->will($this->returnValue($methodParameters));
		$mockReflectionService->expects($this->once())->method('getPropertyNamesByTag')->with($className, 'inject')->will($this->returnValue(array('firstDependency')));

		$mockObjectBuilder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\ObjectBuilder'), array('dummy'), array(), '', FALSE);
		$mockObjectBuilder->injectReflectionService($mockReflectionService);
		$actualProperties = $mockObjectBuilder->_call('autowireProperties', array(), $className);

		$this->assertSame('F3\FLOW3\Object\ObjectManagerInterface', $actualProperties['firstDependency']->getValue());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPropertiesCanDoPropertyInjectionViaReflection() {
		$className = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSetterAndPropertyInjection';
		$object = new $className;
		$setterProperties = array(
			'secondDependency' => new \F3\FLOW3\Object\Configuration\ConfigurationProperty('secondDependency', 'injected value', \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE)
		);

		$mockObjectBuilder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\ObjectBuilder'), array('dummy'), array(), '', FALSE);
		$mockObjectBuilder->_call('injectProperties', $setterProperties, $object);

		$propertyReflection = new \ReflectionProperty($object, 'secondDependency');
		$propertyReflection->setAccessible(TRUE);
		$this->assertSame('injected value', $propertyReflection->getValue($object));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createEmptyObjectReturnsAnObjectOfTheSpecifiedType() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties');

		$object = $this->objectBuilder->createEmptyObject('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $objectConfiguration);
		$this->assertType('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $object);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception\CannotReconstituteObjectException
	 */
	public function createEmptyObjectRejectsObjectTypesWhichAreNotPersistable() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\BasicClass');

		$this->objectBuilder->createEmptyObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createEmptyObjectPreventsThatTheConstructorOfTheTargetObjectIsCalled() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties');

		$object = $this->objectBuilder->createEmptyObject('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $objectConfiguration);
		$this->assertFalse($object->constructorHasBeenCalled);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function reinjectDependenciesTriesToDependencyInjectPropertiesWhichAreNotPersistable() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getPropertyNamesByTag')->will($this->returnValue(array()));

		$objectBuilder = new \F3\FLOW3\Object\ObjectBuilder();
		$objectBuilder->injectObjectManager($this->mockObjectManager);
		$objectBuilder->injectObjectFactory($this->mockObjectFactory);
		$objectBuilder->injectReflectionService($mockReflectionService);

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties');
		$objectConfiguration->setProperty(new \F3\FLOW3\Object\Configuration\ConfigurationProperty('stringDependency', 'wasInjected'));
		$object = $objectBuilder->createEmptyObject('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $objectConfiguration);
		$objectBuilder->reinjectDependencies($object, $objectConfiguration);

		$this->assertEquals('wasInjected', $object->FLOW3_AOP_Proxy_getProperty('stringDependency'));
	}
}
?>