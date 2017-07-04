<?php

namespace BddBundle\Form\Type;

use BddBundle\Entity\Coaster;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CoasterType
 * @package BddBundle\Form\Type
 */
class CoasterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('builtCoaster', BuiltCoasterType::class)
            ->add(
                'openingDate',
                DateType::class,
                [
                    'widget' => 'text',
                    'format' => 'dd-MM-yyyy',
                ]
            )
            ->add(
                'closingDate',
                DateType::class,
                [
                    'widget' => 'text',
                    'format' => 'dd-MM-yyyy',
                    'required' => false,
                ]
            )
            ->add(
                'park',
                EntityType::class,
                [
                    'class' => 'BddBundle\Entity\Park',
                    'choice_label' => 'name',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('p')
                            ->orderBy('p.name', 'ASC');
                    },
                ]
            )
            ->add(
                'status',
                EntityType::class,
                [
                    'class' => 'BddBundle\Entity\Status',
                    'choice_label' => 'name',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->orderBy('s.name', 'ASC');
                    },
                ]
            )
            ->add('video', TextType::class, ['required' => false])
            ->add('save', SubmitType::class, array('label' => 'Create/Update'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Coaster::class,
            )
        );
    }
}