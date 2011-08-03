<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\NewsBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Knp\Bundle\MenuBundle\MenuItem;

use Application\Sonata\NewsBundle\Entity\Comment;

class PostAdmin extends Admin
{
    protected $userManager;

    public function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('author')
            ->add('enabled')
            ->add('title')
            ->add('abstract')
            ->add('content')
            ->add('tags')
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('enabled', null, array('required' => false))
                ->add('author', 'sonata_type_model', array(), array('edit' => 'list'))
                ->add('title')
                ->add('abstract')
                ->add('content')
            ->end()
            ->with('Tags')
                ->add('tags', 'sonata_type_model', array('expanded' => true))
            ->end()
            ->with('Options', array('collapsed' => true))
                ->add('commentsCloseAt')
                ->add('commentsEnabled', null, array('required' => false))
                ->add('commentsDefaultStatus', 'choice', array('choices' => Comment::getStatusList()))
            ->end()
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title')
            ->add('author')
            ->add('enabled')
            ->add('tags')
            ->add('commentsEnabled')
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('enabled')
            ->add('tags', 'orm_many_to_many', array('filter_field_options' => array('expanded' => true, 'multiple' => true)))
            ->add('with_open_comments', 'callback', array(
                'template' => 'SonataAdminBundle:CRUD:filter_callback.html.twig',
                'filter_options' => array(
                    'filter' => array($this, 'getWithOpenCommentFilter'),
                    'type'   => 'checkbox'
                ),
                'filter_field_options' => array(
                    'required' => false
                )
            ))
        ;
    }

    public function getWithOpenCommentFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value) {
            return;
        }

        $queryBuilder->leftJoin(sprintf('%s.comments', $alias), 'c');
        $queryBuilder->andWhere('c.status = :status');
        $queryBuilder->setParameter('status', Comment::STATUS_MODERATE);
    }

    public function preInsert($post)
    {
        parent::preInsert($post);

        if (isset($this->formFieldDescriptions['author'])) {
            $this->getUserManager()->updatePassword($post->getAuthor());
        }
    }

    public function preUpdate($post)
    {
        parent::preUpdate($post);

        if (isset($this->formFieldDescriptions['author'])) {
            $this->getUserManager()->updatePassword($post->getAuthor());
        }
    }

    public function configureSideMenu(MenuItem $menu, $action, Admin $childAdmin = null)
    {
        if (!$childAdmin && !in_array($action, array('edit'))) {
            return;
        }

        $admin = $this->isChild() ? $this->getParent() : $this;

        $id = $admin->getRequest()->get('id');

        $menu->addChild(
            $this->trans('view_post'),
            $admin->generateUrl('edit', array('id' => $id))
        );

        $menu->addChild(
            $this->trans('link_view_comment'),
            $admin->generateUrl('sonata.news.admin.comment.list', array('id' => $id))
        );
    }

    public function setUserManager($userManager)
    {
        $this->userManager = $userManager;
    }

    public function getUserManager()
    {
        return $this->userManager;
    }
}