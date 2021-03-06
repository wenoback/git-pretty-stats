<?php
namespace GitPrettyStats;

use Mockery as m;

/**
 * @covers GitPrettyStats\RepositoryFactory
 */
class RepositoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPathsWithoutConfig ()
    {
        $finder = m::mock('stdClass');

        $firstRepository = m::mock('stdClass');
        $firstRepository->shouldReceive('getRealPath')->once()->andReturn('/absolute/path/repository');

        $secondRepository = m::mock('stdClass');
        $secondRepository->shouldReceive('getRealPath')->once()->andReturn('/absolute/path/other-repository');

        $finder->shouldReceive('depth->directories->in')
            ->once()
            ->with('/var/www/git-pretty-stats/repositories')
            ->andReturn(array($firstRepository, $secondRepository));

        $factory = new RepositoryFactory(null, $finder, '/var/www/git-pretty-stats/');

        $this->assertEquals(
            array('/absolute/path/repository', '/absolute/path/other-repository'),
            $factory->getPaths(),
            'Did not load repositories path without config'
        );
    }

    public function testGetPathsWithConfig ()
    {
        $finder = m::mock('stdClass');
        $config = array(
            'repositoriesPath' => 'non-default-dir'
        );

        $firstRepository = m::mock('stdClass');
        $firstRepository->shouldReceive('getRealPath')->once()->andReturn('/absolute/path/repository');

        $secondRepository = m::mock('stdClass');
        $secondRepository->shouldReceive('getRealPath')->once()->andReturn('/absolute/path/other-repository');

        $finder->shouldReceive('depth->directories->in')
            ->once()
            ->with('/var/www/git-pretty-stats/non-default-dir')
            ->andReturn(array($firstRepository, $secondRepository));

        $factory = new RepositoryFactory($config, $finder, '/var/www/git-pretty-stats/');

        $this->assertEquals(
            array('/absolute/path/repository', '/absolute/path/other-repository'),
            $factory->getPaths(),
            'Did not load repositories path with repository path set in config'
        );
    }

    public function testGetPathsWithConfigArray ()
    {
        $finder = m::mock('stdClass');

        $firstRepositoryPath = '/path/to/first-repo';
        $secondRepositoryPath = '/path/to/second-repo';

        $config = array(
            'repositoriesPath' => array(
                $firstRepositoryPath,
                $secondRepositoryPath
            )
        );

        $firstRepository = m::mock('stdClass');
        $firstRepository->shouldReceive('getRealPath')->once()->andReturn($firstRepositoryPath);

        $secondRepository = m::mock('stdClass');
        $secondRepository->shouldReceive('getRealPath')->once()->andReturn($secondRepositoryPath);

        $finder
            ->shouldReceive('depth->directories->append')
            ->once()
            ->andReturn(array($firstRepository, $secondRepository));

        $factory = new RepositoryFactory($config, $finder, '/var/www/git-pretty-stats/');

        $this->assertEquals(
            array($firstRepositoryPath, $secondRepositoryPath),
            $factory->getPaths(),
            'Did not load repositories paths correctly with repository array set in config'
        );
    }

    public function testRepositoriesSetterAndGetter ()
    {
        $factory = new RepositoryFactory;

        $repositories = array('first-repo', 'second-repo');

        $factory->setRepositories($repositories);

        $this->assertEquals(
            $repositories,
            $factory->getRepositories(),
            'Repositories setter and getter failed'
        );
    }

    public function testAll ()
    {
        $firstRepository = m::mock('stdClass');
        $firstRepository->shouldReceive('getName')->once()->andReturn('first-repo');

        $secondRepository = m::mock('stdClass');
        $secondRepository->shouldReceive('getName')->once()->andReturn('second-repo');

        $factory = m::mock('GitPrettyStats\RepositoryFactory[getPaths,load]');
        $factory
            ->shouldReceive('getPaths')
            ->once()
            ->andReturn(array('/first/path', '/second/path'))
            ->shouldReceive('load')
            ->once()
            ->with('/first/path')
            ->andReturn($firstRepository)
            ->shouldReceive('load')
            ->once()
            ->with('/second/path')
            ->andReturn($secondRepository);

        $this->assertEquals(
            array('first-repo' => $firstRepository, 'second-repo' => $secondRepository),
            $factory->all(),
            'Did not return all repositories'
        );
    }

    public function testAllLazyLoad ()
    {
        $factory = new RepositoryFactory;

        $repositories = array(
            'first-repo' => '/path',
            'second-repo' => '/other-path'
        );

        $factory->setRepositories($repositories);

        $this->assertEquals(
            $repositories,
            $factory->all(),
            'Lazy load for repositories failed'
        );
    }

    public function testFromName ()
    {
        $factory = new RepositoryFactory;

        $repositories = array(
            'first-repo' => '/path',
            'second-repo' => '/other-path'
        );

        $factory->setRepositories($repositories);

        $this->assertEquals(
            '/path',
            $factory->fromName('first-repo'),
            'From name returned incorrect value'
        );
    }


    public function testToArray ()
    {
        $gitter = m::mock('stdClass');
        $gitter->shouldReceive('getCurrentBranch')->twice()->andReturn('master');

        $firstRepository = m::mock('stdClass');
        $firstRepository->gitter = $gitter;
        $firstRepository
            ->shouldReceive('getName')
            ->twice()
            ->andReturn('first-repo')
            ->shouldReceive('countCommitsFromGit')
            ->once()
            ->andReturn(271);

        $secondRepository = m::mock('stdClass');
        $secondRepository->gitter = $gitter;
        $secondRepository
            ->shouldReceive('getName')
            ->twice()
            ->andReturn('second-repo')
            ->shouldReceive('countCommitsFromGit')
            ->once()
            ->andReturn(173);

        $factory = m::mock('GitPrettyStats\RepositoryFactory[all]');
        $factory->shouldReceive('all')->once()->andReturn(array($firstRepository, $secondRepository));

        $this->assertEquals(
            array(
                'first-repo' => array(
                    'name'    => 'first-repo',
                    'commits' => 271,
                    'branch'  => 'master'
                ),
                'second-repo' => array(
                    'name'    => 'second-repo',
                    'commits' => 173,
                    'branch'  => 'master'
                )
            ),
            $factory->toArray(),
            'Did not return all repositories'
        );
    }
}
