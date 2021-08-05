<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Country;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignType extends AbstractType
{
    /**
     * @var CountryRepository
     */
    private $countryRepository;

    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choices' => $this->countryRepository->findAll(),
                'choice_label' => function (Country $country) {
                    return $country->getTitle() . " (" . $country->getShort().  ")";
                },
                'help' => 'Select a campaign country',
            ])
            ->add('return', SubmitType::class, [
                'label' => 'Create and return',
                'attr' => array('class' => 'btn-primary btn-block')
            ])
            ->add('manage', SubmitType::class, [
                'label' => 'Create and manage',
                'attr' => array('class' => 'btn-success btn-block')
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Campaign::class
        ]);
    }
}