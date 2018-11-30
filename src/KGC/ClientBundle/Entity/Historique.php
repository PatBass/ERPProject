<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\CommonBundle\Traits as CommonTraits;
use KGC\RdvBundle\Entity\RDV;
use KGC\UserBundle\Entity\Utilisateur;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Table(name="client_historique", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="historique_idx", columns={"type", "rdv_id", "client_id", "consultant_id", "mail_id"})
 * })
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\HistoriqueRepository")
 */
class Historique implements \KGC\Bundle\SharedBundle\Entity\Interfaces\Historique
{
    use ORMBehaviors\Timestampable\Timestampable;
    use CommonTraits\Constantable;

    const TYPE_BEHAVIOR = 'behavior';
    const TYPE_PROFILE = 'profile';
    const TYPE_SITUATION = 'situation';
    const TYPE_HUSBAND_FIRSTNAME = 'husband_firstname';
    const TYPE_OTHER_FIRSTNAME = 'other_firstname';
    const TYPE_PRO_SITUATION = 'pro_situation';
    const TYPE_JOB = 'job';
    const TYPE_PROBLEMS = 'problems';
    const TYPE_OBJECTIVE = 'objective';
    const TYPE_MEANS = 'means';
    const TYPE_SENDING = 'sending';
    const TYPE_PRODUCT = 'product';
    const TYPE_PLAN = 'plan';
    const TYPE_FREE_NOTES = 'free_notes';
    const TYPE_PENDULUM = 'pendulum';
    const TYPE_DRAW = 'draw';
    const TYPE_MAIL = 'mail';
    const TYPE_SMS = 'sms';
    const TYPE_REMINDER = 'reminder';
    const TYPE_RECAP = 'recap';
    const TYPE_NOTES = 'notes';
    const TYPE_OPINION = 'opinion';
    const TYPE_REMINDER_STATE = 'reminder_state';
    const TYPE_RECURRENT = 'recurrent';
    const TYPE_STOP_FOLLOW = 'stop_follow';

    const BACKEND_TYPE_STRING = 'string';
    const BACKEND_TYPE_TEXT = 'text';
    const BACKEND_TYPE_BOOL = 'bool';
    const BACKEND_TYPE_OPTION = 'option';
    const BACKEND_TYPE_OPTIONS = 'options';
    const BACKEND_TYPE_PENDULUM = 'pendulum';
    const BACKEND_TYPE_DATETIME = 'datetime';
    const BACKEND_TYPE_MAIL = 'mail';
    const BACKEND_TYPE_SMS = 'sms';
    const BACKEND_TYPE_DRAW = 'draw';

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $type;

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\RDV", inversedBy="notesVoyant")
     * @ORM\JoinColumn(referencedColumnName="rdv_id", onDelete="CASCADE")
     */
    protected $rdv;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Client
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", inversedBy="historique")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(referencedColumnName="uti_id")
     */
    protected $consultant;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=256, nullable=false)
     */
    protected $backendType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    protected $string;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $text;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetime;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $bool = null;

    /**
     * @var Option
     * @ORM\ManyToOne(targetEntity="Option")
     */
    protected $option;

    /**
     * @var Pendulum
     *
     * @ORM\OneToMany(targetEntity="Pendulum", mappedBy="history", cascade={"refresh", "persist", "detach", "remove"})
     */
    protected $pendulum;

    /**
     * @var Draw
     *
     * @ORM\OneToMany(targetEntity="Draw", mappedBy="history", cascade={"refresh", "persist", "detach", "remove"})
     */
    protected $draw;

    /**
     * @var MailSent
     *
     * @ORM\OneToOne(targetEntity="MailSent", cascade={"refresh", "persist", "detach", "remove"})
     */
    protected $mail;

    /**
     * @var SmsSent
     *
     * @ORM\OneToOne(targetEntity="SmsSent", cascade={"refresh", "persist", "detach", "remove"})
     */
    protected $sms;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Option", cascade={"refresh", "persist"})
     * @ORM\JoinTable(name="value_option",
     *     joinColumns={@ORM\JoinColumn(name="historique_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="option_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")}
     * )
     */
    protected $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->pendulum = new ArrayCollection();
        $this->draw = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        if (!in_array($type, $this->buildByPrefixes('TYPE'))) {
            throw new \InvalidArgumentException(
                sprintf('Historique type unknown "%s"', $type)
            );
        }

        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getRdv()
    {
        return $this->rdv;
    }

    /**
     * @param mixed $rdv
     */
    public function setRdv(RDV $rdv)
    {
        $this->rdv = $rdv;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        $client->addHistorique($this);
    }

    /**
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getConsultant()
    {
        return $this->consultant;
    }

    /**
     * @param \KGC\UserBundle\Entity\Utilisateur $consultant
     */
    public function setConsultant(Utilisateur $consultant)
    {
        $this->consultant = $consultant;
    }

    /**
     * @return string
     */
    public function getBackendType()
    {
        return $this->backendType;
    }

    /**
     * @param string $backendType
     */
    public function setBackendType($backendType)
    {
        if (!in_array($backendType, $this->buildByPrefixes('BACKEND_TYPE'))) {
            throw new \InvalidArgumentException(
                sprintf('Historique backend type unknown "%s"', $backendType)
            );
        }
        $this->backendType = $backendType;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @param string $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }

    /**
     * @return mixed
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param mixed $datetime
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
    }

    /**
     * @return mixed
     */
    public function getBool()
    {
        return $this->bool;
    }

    /**
     * @param mixed $boolean
     */
    public function setBool($boolean)
    {
        $this->bool = $boolean;
    }

    /**
     * @return Option
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param Option $option
     */
    public function setOption($option)
    {
        $this->option = $option;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param Option $option
     *
     * @return $this
     */
    public function addOption(Option $option)
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
        }

        return $this;
    }

    /**
     * @param Option $option
     *
     * @return $this
     */
    public function removeOption(Option $option)
    {
        $this->options->removeElement($option);

        return $this;
    }

    /**
     * @return Pendulum
     */
    public function getPendulum()
    {
        return $this->pendulum;
    }

    /**
     * @param ArrayCollection $pendulum
     */
    public function setPendulum($pendulum)
    {
        foreach ($pendulum as $p) {
            $p->setHistory($this);
        }

        $this->pendulum = $pendulum;
    }

    /**
     * @param Pendulum $pendulum
     *
     * @return $this
     */
    public function addPendulum(Pendulum $pendulum)
    {
        if (!$this->pendulum->contains($pendulum)) {
            $this->pendulum->add($pendulum);
        }

        return $this;
    }

    /**
     * @param Pendulum $pendulum
     *
     * @return $this
     */
    public function removePendulum(Pendulum $pendulum)
    {
        $this->pendulum->removeElement($pendulum);

        return $this;
    }

    public function getValue()
    {
        $getMethod = sprintf('get%s', ucfirst($this->backendType));

        return $this->$getMethod();
    }

    /**
     * @return MailSent
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * @param MailSent $mail
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    }

    /**
     * @return SmsSent
     */
    public function getSms()
    {
        return $this->sms;
    }

    /**
     * @param SmsSent $sms
     */
    public function setSms($sms)
    {
        $this->sms = $sms;
    }

    /**
     * @return Draw
     */
    public function getDraw()
    {
        return $this->draw;
    }

    /**
     * @param array $draws
     */
    public function setDraw($draws)
    {
        $this->draw = new ArrayCollection();
        foreach ($draws as $d) {
            $this->addDraw($d);
        }
    }

    /**
     * @param Draw $draw
     *
     * @return $this
     */
    public function addDraw(Draw $draw)
    {
        if (!$this->draw->contains($draw)) {
            $draw->setHistory($this);
            $this->draw->add($draw);
        }

        return $this;
    }

    /**
     * @param Draw $draw
     *
     * @return $this
     */
    public function removeDraw(Draw $draw)
    {
        $this->draw->removeElement($draw);

        return $this;
    }
}
