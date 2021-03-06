<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Aspect;

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

/**
 * Testcase for the Dirty Monitoring Aspect
 *
 * @version $Id: DirtyMonitoringAspectTest.php 3643 2010-01-15 14:38:07Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DirtyMonitoringAspectTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanStateWithoutArgumentHandlesAllProperties() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array('foo' => 1, 'bar' => 1)));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect();
		$aspect->injectReflectionService($mockReflectionService);

		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->at(0))->method('FLOW3_AOP_Proxy_getProperty')->with('foo');
		$object->expects($this->at(1))->method('FLOW3_AOP_Proxy_getProperty')->with('foo');
		$object->expects($this->at(2))->method('FLOW3_AOP_Proxy_getProperty')->with('bar');
		$object->expects($this->at(3))->method('FLOW3_AOP_Proxy_getProperty')->with('bar');
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($object));

		$aspect->memorizeCleanState($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanStateWithArgumentHandlesSpecifiedProperty() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect();

		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->exactly(2))->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue('bar'));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->exactly(2))->method('getMethodArgument')->will($this->returnValue('foo'));

		$aspect->memorizeCleanState($mockJoinPoint);
		$this->assertEquals($object->FLOW3_Persistence_cleanProperties['foo'], 'bar');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanStateClonesObjects() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect();

		$value = new \stdClass();
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->exactly(2))->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue($value));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->exactly(2))->method('getMethodArgument')->will($this->returnValue('foo'));

		$aspect->memorizeCleanState($mockJoinPoint);
		$this->assertEquals($object->FLOW3_Persistence_cleanProperties['foo'], $value);
		$this->assertNotSame($object->FLOW3_Persistence_cleanProperties['foo'], $value);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyDetectsChangesInLiterals() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect();
		$aspect->injectReflectionService($mockReflectionService);
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue('bar'));
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnValue('foo'));

		$object->FLOW3_Persistence_cleanProperties = array('foo' => 'bar');
		$this->assertFalse($aspect->isDirty($mockJoinPoint));

		$object->FLOW3_Persistence_cleanProperties = array('foo' => 'baz');
		$this->assertTrue($aspect->isDirty($mockJoinPoint));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyDetectsChangesInObjectsByCallingAreObjectsDifferent() {
		$value = new \stdClass();
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue($value));
		$object->FLOW3_Persistence_cleanProperties = array('foo' => clone $value);

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnValue('foo'));

		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect', array('areObjectsDifferent'));
		$aspect->injectReflectionService($mockReflectionService);
		$aspect->expects($this->any())->method('areObjectsDifferent')->will($this->returnValue(TRUE));

		$this->assertTrue($aspect->isDirty($mockJoinPoint));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentConsidersClonedPlainObjectAsEqual() {
		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));

		$object1 = new \stdClass();
		$object1->foo = 'bar';
		$object2 = clone $object1;
		$this->assertFalse($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentConsidersEqualPlainObjectAsEqual() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));
		$aspect->injectReflectionService($mockReflectionService);

		$object1 = new \stdClass();
		$object1->foo = 'bar';
		$object2 = new \stdClass();
		$object2->foo = 'bar';
		$this->assertFalse($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentConsidersDifferentPlainObjectsOfSameTypeAsDifferent() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));
		$aspect->injectReflectionService($mockReflectionService);

		$object1 = new \stdClass();
		$object1->foo = 'bar';
		$object2 = new \stdClass();
		$object2->foo = 'baz';
		$this->assertTrue($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentConsidersPlainObjectsOfDifferentTypeAsDifferent() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));
		$aspect->injectReflectionService($mockReflectionService);

		$object1 = new \stdClass();
		$object2 = new \DateTime();
		$this->assertTrue($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentChecksPropertiesFromClassSchema() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array('foo' => array())));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));
		$aspect->injectReflectionService($mockReflectionService);

		$object1 = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object1->expects($this->any())->method('FLOW3_AOP_Proxy_getProxyTargetClassName')->will($this->returnValue(get_class($object1)));
		$object1->expects($this->at(1))->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue('bar'));
		$object1->expects($this->at(2))->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue('baz'));
		$object1->foo = 'exists';
		$object2 = clone $object1;

		$this->assertTrue($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentUsesStrictComparisonOnObjectsInProperties() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array('foo' => array())));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));
		$aspect->injectReflectionService($mockReflectionService);

		$object1 = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object1->expects($this->any())->method('FLOW3_AOP_Proxy_getProxyTargetClassName')->will($this->returnValue(get_class($object1)));
		$object1->expects($this->at(1))->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue(new \stdClass()));
		$object1->expects($this->at(2))->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue(new \stdClass()));
		$object1->foo = 'exists';
		$object2 = clone $object1;

		$this->assertTrue($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentIgnoresExtraProperties() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array('foo' => array())));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));
		$aspect->injectReflectionService($mockReflectionService);

		$object1 = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object1->expects($this->any())->method('FLOW3_AOP_Proxy_getProxyTargetClassName')->will($this->returnValue(get_class($object1)));
		$object1->expects($this->exactly(2))->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue('bar'));
		$object1->foo = 'exists';
		$object2 = clone $object1;
		$object2->quux = 'exists';

		$this->assertFalse($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function areObjectsDifferentDetectsMissingProperties() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array('foo' => array())));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));

		$aspect = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect'), array('dummy'));
		$aspect->injectReflectionService($mockReflectionService);

		$object1 = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object1->expects($this->any())->method('FLOW3_AOP_Proxy_getProxyTargetClassName')->will($this->returnValue(get_class($object1)));
		$object1->foo = 'bar';
		$object2 = clone $object1;
		unset($object2->foo);

		$this->assertTrue($aspect->_call('areObjectsDifferent', $object1, $object2));
	}

	/**
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObjectMarksTheObjectAsCloned() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect();

		$object = new \stdClass();
		$object->FLOW3_Persistence_cleanProperties = array('foo');

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$this->assertFalse($aspect->isClone($mockJoinPoint));
		$aspect->cloneObject($mockJoinPoint);
		$this->assertTrue($aspect->isClone($mockJoinPoint));
	}

	/**
	 * @test
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNewIsTrueAfterCloneObject() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect();
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->FLOW3_Persistence_cleanProperties = array('foo');
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$this->assertFalse($aspect->isNew($mockJoinPoint));
		$aspect->cloneObject($mockJoinPoint);
		$this->assertTrue($aspect->isNew($mockJoinPoint));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function newObjectsAreDirty() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect();
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$this->assertTrue($aspect->isDirty($mockJoinPoint));
	}

}

?>