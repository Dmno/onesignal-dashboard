<?php

namespace App\Form;

use App\Entity\Schedule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $date = \DateTimeImmutable::createFromFormat('d/m/Y H:i', date('d/m/Y H:i'));
        $builder
            ->add('delivery', ChoiceType::class, [
                'choices' => [
                    "Begin sending immediately" => "immediately",
                    "Begin sending at a particular time" => "particular time"
                ]
            ])
            ->add('date', DateTimeType::class, [
                'help' => 'Day/Month/Year',
                'html5' => false,
//                'format' => 'dd/MM/yyyy',
                'widget' => 'single_text'
            ])
            ->add('optimisation', ChoiceType::class, [
                'choices' => [
                    "Send immediately" => "immediately"
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Schedule::class
        ]);
    }
}