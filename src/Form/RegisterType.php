<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => array(
                    'autocomplete' => 'off',
                    'autofocus' => true
                ),
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a username',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Your username should be at least {{ limit }} characters long',
                        'max' => 50,
                    ]),
                ]
            ])
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'label' => "Password",
                'attr' => array(
                    'autocomplete' => 'off',
                ),
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('invite', TextType::class, [
                'mapped' => false,
                'label' => "Invite code",
                'attr' => array(
                    'autocomplete' => 'off',
                ),
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please provide an invite code',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'The invite code is too short',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('Register', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id'   => 'form_intention',
        ]);
    }
}