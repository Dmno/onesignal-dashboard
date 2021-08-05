<?php

namespace App\Form;

use App\Entity\Country;
use App\Form\Model\NotificationAndScheduleDuplicationModel;
use App\Form\Model\NotificationAndScheduleModel;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NotificationAndScheduleDuplicateType extends AbstractType
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
            ->add('name', TextType::class, [
                'help' => 'Name this notification',
                'attr' => array(
                    'placeholder' => 'My notification 1',
                    'class' => 'notification_name'
                ),
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 3
                    ])
                ]
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choices' => $this->countryRepository->findAll(),
                'choice_label' => function (Country $country) {
                    return $country->getTitle() . " (" . $country->getShort().  ")";
                },
                'help' => 'Select a country to send a notification to',
                'attr' => array(
                    'class' => 'notification_country'
                ),
            ])
            ->add('title', TextType::class, [
                'help' => 'Notification title',
                'attr' => array(
                    'class' => 'notification_title'
                ),
            ])
            ->add('message', TextType::class, [
                'help' => 'Notification message',
                'attr' => array(
                    'class' => 'notification_message'
                ),
            ])
            ->add('icon', TextType::class, [
                'help' => 'Select an icon or upload a new one',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'class' => 'image-type-selector notification_icon',
                    'id' => 'icon-select'
                ],
                'empty_data' => ''
            ])
            ->add('image', TextType::class, [
                'help' => 'Select an image or upload a new one',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'class' => 'image-type-selector notification_image',
                    'id' => 'image-select'
                ],
                'empty_data' => ''
            ])
            ->add('url', TextType::class, [
                'help' => 'Insert a launch url',
                'label' => 'Launch url',
                'required' => true,
                'attr' => array(
                    'class' => 'notification_url'
                ),
            ])
            ->add('delivery', ChoiceType::class, [
                'choices' => [
                    "Begin sending immediately" => "immediately",
                    "Begin sending at a particular time" => "particular time"
                ],
                'attr' => array(
                    'class' => 'notification_delivery'
                ),
            ])
            ->add('date', DateTimeType::class, [
                'help' => '2021-12-31 09:32',
                'html5' => false,
                'widget' => 'single_text',
                'input' => 'string',
                'format' => 'yyyy-MM-dd HH:mm',
                'data' => '',
                'attr' => array(
                    'class' => 'notification_schedule'
                ),
            ])
            ->add('optimisation', ChoiceType::class, [
                'choices' => [
                    "Send immediately" => "immediately"
                ],
                'attr' => array(
                    'class' => 'notification_optimisation'
                ),
            ])
            ->add('store', CheckboxType::class, [
                'label'    => 'Store this notification without sending',
                'required' => false,
                'attr' => array(
                    'class' => 'notification_store'
                ),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NotificationAndScheduleModel::class,
        ]);
    }
}