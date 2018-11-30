<?php

namespace KGC\ChatBundle\Twig\Extension;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Service\TokenManager;
use KGC\UserBundle\Entity\Utilisateur as Psychic;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @DI\Service("kgc.chat.twig.extension")
 *
 * @DI\Tag("twig.extension")
 */
class ChatExtension extends \Twig_Extension
{
    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TokenManager        $tokenManager
     * @param TranslatorInterface $translator
     *
     * @DI\InjectParams({
     *     "tokenManager"  = @DI\Inject("kgc.chat.token.manager"),
     *     "translator"  = @DI\Inject("translator")
     * })
     */
    public function __construct(TokenManager $tokenManager, TranslatorInterface $translator)
    {
        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_chat_token', [$this, 'getChatToken']),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('transkeys', array($this, 'transkeysFilter')),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'chat';
    }

    /**
     * Bridge for TokenManager->getToken().
     *
     * @return string
     */
    public function getChatToken(Psychic $user)
    {
        return $this->tokenManager->getToken($user);
    }

    /**
     * Fetch translator to get all keys starting with this id.
     *
     * @return string
     */
    public function transkeysFilter($id, $domain = 'messages')
    {
        $keys = array();

        $id_length = strlen($id);

        $g = $this->translator->getMessages();
        foreach ($g[$domain] as $key => $message) {
            if (substr($key, 0, $id_length) === $id) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
