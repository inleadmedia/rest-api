<?php
namespace Bpi\ApiBundle\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Bpi\ApiBundle\Domain\Aggregate\Agency;
use Bpi\ApiBundle\Domain\Aggregate\Params;
use Bpi\ApiBundle\Domain\Entity\Author;
use Bpi\ApiBundle\Domain\Entity\Audience;
use Bpi\ApiBundle\Domain\Entity\Category;
use Bpi\ApiBundle\Domain\ValueObject\Yearwheel;
use Bpi\ApiBundle\Domain\ValueObject\Copyleft;
use Bpi\ApiBundle\Domain\ValueObject\Param\Editable;
use Bpi\ApiBundle\Domain\ValueObject\Param\Authorship;
use Bpi\ApiBundle\Domain\Factory\ResourceBuilder;
use Bpi\ApiBundle\Domain\Factory\ProfileBuilder;
use Bpi\ApiBundle\Domain\Service\PushService;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Bpi\ApiBundle\Domain\Entity\History;

class FakeData implements FixtureInterface
{
    /**
     * FilesystemMap used in createFilesystemMap.
     *
     * @var \Knp\Bundle\GaufretteBundle\FilesystemMap
     */
    protected $fsMap = NULL;

    /**
     * @return \Knp\Bundle\GaufretteBundle\FilesystemMap
     */
    protected function createFilesystemMap()
    {
        if ($this->fsMap == NULL) {
            $this->fsMap = new FilesystemMap(array('assets' => new \Gaufrette\Filesystem(new \Gaufrette\Adapter\InMemory())));
        }
        return $this->fsMap;
    }

    protected function createResourceBuilder() {
        $fs = $this->createFilesystemMap()->get('assets');
        $router = new FakeRouter();
        return new ResourceBuilder($fs, $router);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

        $this->createCategories($manager);
        $this->createAudiences($manager);

        $service = new PushService($manager, $this->createFilesystemMap());

        $agency['Arhus'] = new Agency('200100', 'Aarhus Kommunes Biblioteker', 'Agency Moderator Name', md5('agency_200100_public'), sha1('agency_200100_secret'));
        $agency['Kobenhavns'] = new Agency('200200', 'Københavns Biblioteker', 'Københavns Moderator Name', md5('agency_200200_public'), sha1('agency_200200_secret'));
        $agency['Halsnas'] = new Agency('200300', 'Halsnæs Kommune - Bibliotekerne', 'Halsnæs Moderator Name', md5('agency_200300_public'), sha1('agency_200300_secret'));

        foreach($agency as $agency_item) {
            $manager->persist($agency_item);
        }

        $manager->flush();

        // 1 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
            ->body('<p>For de helt små børn kan de første filmoplevelser være meget overvældende. Derfor er det godt, at der findes film som fortæller historier i et roligt tempo og med en lykkelig slutning. Det Danske Filminstitut har indkøbt en række charmerende svenske tegnefilm, som trygt kan ses af børn fra 3-års alderen.</p><p><strong>Ørnen der havde højdeskræk</strong><br />I filmen &quot;Ørnen der havde højdeskræk&quot; kan kongeørnen Orla ikke lide at flyve. Han bliver så ør og svimmel af højder, men en fuglekonge lokker ham højere og højere op i luften, og til sidst opdager Orla, at han sagtens kan svæve sammen med de andre ørne.</p><p><strong>Elsa siger godnat</strong><br />I denne animationsfilm vil Elsa gerne høre en godnathistorie inden hun skal sove. Heldigvis har alle sovedyrene både lyst og tid. Alle har de oplevet usædvanlige ting og de vil meget gerne fortælle historierne. I aften er det Harry Hamster, der fortæller Elsa en historie om den mest berømte detektiv i skoven.</p><p><strong>Filmstriben.dk </strong><br />Se filmene sammen med dit barn, trygt og godt hjemme fra sofaen. Log på <a href="http://filmstriben.dk">Filmstriben.dk</a> med dit lånernr,/cpr.nr. og din pinkode!</p>')
            ->teaser('For de helt små børn kan de første filmoplevelser være meget overvældende. Derfor er det godt, at der findes film som fortæller historier i et roligt tempo og med en lykkelig slutning. Se dem på filmstriben.dk')
            ->title('Ti tegnefilm for de mindste på filmstriben')
            ->ctime(new \DateTime("-10 day"))
            ->copyleft(new Copyleft(''))
            ->addMaterial('123-100200:12345678')
            ->addMaterial('123-100200:87654321')
            ->url('http://example.com/article')
            ->data(json_encode(array('subject' => 'computer science')))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Winter'))
            ->tags('foo, bar, zoo')
            ->build();

        $node1 = $service->push(
            new Author($agency['Arhus']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Film',
            'Kids',
            $profile,
            new Params(array(new Editable(1), new Authorship(1)))
        );

        // 2 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>Karenstiden på musik ophører pr. 1. februar. Tidligere kulturminister, Uffe Elbæk,&nbsp;har&nbsp;ophævet karenstidsordningen for bibliotekernes udlån af musik fra udgangen af 2012.</p><p>Der har siden 2005 været en karenstidsordning, der gør, at bibliotekerne først må udlåne musik-cd&rsquo;er fire måneder efter udgivelsesdatoen. Begrundelsen for karenstidsordningen var en øget kopiering af musik-cd&rsquo;er, men eftersom mange efterhånden også hører meget musik via internettet, er karenstidsordningen ikke længere så relevant.</p><p>Med ophævelsen af ordningen kan vi tilbyde alle vores musikglade lånere et bedre, mere smidigt og aktuelt udvalg af musik-cd&acute;er.</p>')
              ->teaser('Karenstiden på musik ophører pr. 1. februar.')
              ->title('Glad for musik? Så er der gode nyheder!')
              ->ctime(new \DateTime("-11 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Winter'))
            ->tags('foo, bar, zoo')
            ->build();
        ;

        $node2 = $service->push(
            new Author($agency['Arhus']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Music',
            'Adult',
            $profile,
            new Params(array(new Editable(1), new Authorship(1)))
        );

        // 3 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p><em><a name="top">﻿</a></em></p><p><em>Skrevet af Jeanette Jensen</em></p><p>Så fik vi taget hul på det nye år og nu er det tid til at komme i gang med alt det, som vi lovede os selv, før klokken slog 12 den 31. december.</p><p>Nedenfor har jeg samlet nogle bøger, lydbøger og andre tips, så I har et sted at starte.</p><p>Jeg har taget udgangspunkt i nogle af de klassiske nytårsforsæt,&nbsp;sundere kost, mere motion og rygestop, men har også taget et par nyklassikere med: Afspænding/mindfulness og pleje af parfoholdet.</p><p>Jeg håber, at I finder inspiration til at tage hul på et godt nyt år!</p><p><a href="#Sundere kost">Sundere kost</a></p><p><a href="#Mere motion">Mere motion</a></p><p><a href="#Rygestop">Rygestop</a></p><p><a href="#parforhold">Plej dit parforhold</a></p><p><a href="#Afspænding">Afspænding</a></p><h3><strong><a name="Sundere kost">﻿</a></strong></h3><h3><strong>Sundere kost</strong></h3><p>Du har hørt det før: slankekure giver ingen varig effekt. Hvis du skal tabe dig og holde vægten har du brug for kostomlægning. Jeg har nu alligevel fundet nogle bøger frem om begge dele. Nogle gange har man bare brug for et hurtigt resultat, for at holde motivationen oppe.</p><p>[[{"type":"media","view_mode":"media_original","fid":"435","attributes":{"alt":"","class":"media-image","height":"323","style":"WIDTH: 100px; HEIGHT: 108px","width":"300"}}]]</p><p>I <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28168128">Slankekur for sultne mænd</a>, giver Lene Hansson sit bud på&nbsp;kostprincipper, opskrifter og motion specielt med henblik på&nbsp;mænd.</p><p>[[{"type":"media","view_mode":"media_original","fid":"436","attributes":{"alt":"","class":"media-image","height":"720","style":"WIDTH: 100px; HEIGHT: 144px","width":"500"}}]]</p><p>Charlotte Hartvig lover intet mindre en et vægttab på et kilo om ugen, hvis man følger hendes kur: <a href="https://taarnbybib.dk/da/ting/object/718500%3A26069335">Skrump dig glad</a>. Bogen har været meget populær på biblioteket de seneste år.</p><p>[[{"type":"media","view_mode":"media_original","fid":"464","attributes":{"alt":"","class":"media-image","height":"191","style":"WIDTH: 100px; HEIGHT: 143px","width":"134"}}]]</p><p>En af de nyere populære diæter&nbsp;er <a href="https://taarnbybib.dk/da/ting/collection/718500%3A29521255">Dukan-diaten</a>, hvor den franske læge Pierre Dukan præsenterer sin diæt baseret på højt proteinindtag. Ud over opskrifter indeholder bogen også&nbsp;forslag til motion og øvelser.</p><p>[[{"type":"media","view_mode":"media_original","fid":"465","attributes":{"alt":"","class":"media-image","height":"194","style":"WIDTH: 100px; HEIGHT: 153px","width":"127"}}]]</p><p>Et andet trendy koncept at spise ud fra pt. er stenalderkost, der bl.a. går ud på at spise så mange ikke forarbejdede fødevarer som muligt, spise masser af kød og fisk og undgå korn- og mejeriprodukter. Der er selvfølgelig også lavet&nbsp;en stenalderkost-kur: <a href="https://taarnbybib.dk/da/ting/collection/718500%3A29562458">Loren Cordain: Stenalderkost kuren : sund på 7 dage</a></p><p>Den mest omtalte bog om stenalderkost pt. må være <a href="https://taarnbybib.dk/da/ting/collection/718500%3A29603030">Stenalderkost: palæo-diæter til det moderne menneske </a>af den danske gourmet-kok Thomas Rode.</p><p>[[{"type":"media","view_mode":"media_original","fid":"437","attributes":{"alt":"","class":"media-image","height":"395","style":"WIDTH: 100px; HEIGHT: 132px","width":"300"}}]]</p><p>I <a href="https://taarnbybib.dk/da/ting/collection/718500%3A29143315">Verdens bedste kur : vægttab der holder</a>&nbsp;præsenterer&nbsp;Professor Arne Astrup og kendis-kok Christian Bitz kostprincipper og opskrifter på slankeretter, baseret på verdens største videnskabelige undersøgelse om kost og vægttab, Diogenes-undersøgelsen.</p><p>I efterfølgeren <a href="https://taarnbybib.dk/da/ting/collection/718500%3A29492301">Verdens bedste kur vol. 2.0</a> fokuserer Bitz og Astrup på at tilpasse kuren til singler og andre, der er alene på kur.</p><p>[[{"type":"media","view_mode":"media_original","fid":"438","attributes":{"alt":"","class":"media-image","height":"430","style":"WIDTH: 100px; HEIGHT: 143px","width":"300"}}]]<br />Christian Bitz har også lavet sin egen bog <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28070764">Bitz din sundhed : fra råd til retter</a>, hvor han giver tips og råd til et sundt liv, der stadig levner plads til det søde og sjove. &nbsp;</p><p><br /><a href="https://taarnbybib.dk/da/search/ting/dc.subject%3D61.38?text=dc.subject=61.38&amp;facets[]=facet.acSource:bibliotekets materialer&amp;facets[]=facet.type%3Abog">Klik her for at finde flere bøger om sund kost</a></p><p><a href="https://taarnbybib.dk/da/search/ting/dc.subject%3Dslankekure">Klik her for at finde flere bøger om slankekure</a></p><p>Vidste du i øvrigt, at Tårnby Kommune tilbyder <a href="http://www.taarnby.dk/sundhed_og_forebyggelse/sundhedscenter_taarnby/tilbud_og_kurser/sma_skridt_vaegtstopradgivning.htm">gratis vægtstoprådgivning</a>?</p><p>På Fødevarestyrelsens side <a href="http://www.altomkost.dk/Forside.htm">Alt om kost </a>kan du finde masser af gode råd om sund kost til alle aldre og typer</p><p><strong>Sidst, men ikke mindst</strong>, så husk de vise ord fra&nbsp;Chris MacDonald : &rdquo;Du bliver, hvad du spiser, og du spiser, hvad du køber&rdquo;. Så drop slikket og de fede varer allerede i indkøbskurven og fyld den med grøntsager og andre sunde ting i stedet. Det gør det meget lettere at vælge sundt, når du sidder tirsdag aften og&nbsp;trænger til&nbsp;noget at tygge i.</p><p style="text-align: right"><a href="#top">Til toppen</a></p><h3><a name="Mere motion">﻿</a></h3><h3>Mere motion</h3><p>Skal man igang med at motionere, vælger mange i disse år, at begynde at løbe. Løb er sundt, gratis (når man først har udstyret) og fleksibelt i en travl hverdag. Vi har flere bøger om, der henvender sig direkte til begyndere, men selvfølgelig også bøger, der kan hjælpe den mere erfarne løber til at løbe længere eller hurtigere.</p><p>[[{"type":"media","view_mode":"media_original","fid":"439","attributes":{"alt":"","class":"media-image","height":"430","style":"WIDTH: 100px; HEIGHT: 143px","width":"300"}}]]</p><p>I Søren Hjerrild Mikkelsens&nbsp;<a href="https://taarnbybib.dk/da/ting/collection/718500%3A29055653">Kom godt i gang med løb - og bliv ved! </a>kan du læse om alt fra udstyr, kost, væske, søvn og rygning i forbindelse med løb til typiske begynderfejl og løbeskader. I bogen finder du selvfølgelig også helt konkrete træningsprogrammer, så du ved, hvordan du bedst starter.</p><p>[[{"type":"media","view_mode":"media_original","fid":"440","attributes":{"alt":"","class":"media-image","height":"425","style":"WIDTH: 100px; HEIGHT: 142px","width":"300"}}]]</p><p>Også tidligere eliteløber, Rikke Rønholt, giver i sin bog, <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28736215">Bliv løber for livet</a>,&nbsp;anvisninger til, hvordan man kommer igang med at løbe og tilpasser det til sin hverdag. I bogen lægges også vægt på, at der ikke er én rigtig måde at løbe på. Ikke alle skal f.eks. løbe et marathon. Mindre kan også gøre det. Igen findes træningsprogrammer, der kan hjælpe igang.</p><p>[[{"type":"media","view_mode":"media_original","fid":"441","attributes":{"alt":"","class":"media-image","height":"456","style":"WIDTH: 100px; HEIGHT: 152px","width":"300"}}]]</p><p>I <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28670478">Løb dig slank : flad mave og fast bagdel</a> giver maratonløber Christina Bølling&nbsp;gode råd og praktiske tips til såvel begynderen som den mere erfarne løber.</p><p>Vidste du i øvrigt, at Tårnby Kommune tilbyder <a href="http://www.taarnby.dk/sundhed_og_forebyggelse/sundhedscenter_taarnby/tilbud_og_kurser/loebetraening_2012.htm">løbetræning i samarbejde med Amager Atletik Club?</a></p><p>Er du ikke til løb kan du måske finde inspiration til alligevel at få gang i motionen med følgende bøger:</p><p>Har du ikke råd til en personlig træner, der kan svinge pisken over dig, kan du alligevel få del i deres gode råd, da flere af dem, udgiver bøger med deres metoder til at holde sig fit.</p><p>[[{"type":"media","view_mode":"media_original","fid":"442","attributes":{"alt":"","class":"media-image","height":"355","style":"WIDTH: 100px; HEIGHT: 118px","width":"300"}}]]</p><p>F.eks. har&nbsp;Ida Krak udgivet bogen, <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28670397">Drømmekrop : spis, tænk og træn dig sund</a>, med et træningsprogram,&nbsp;der kombinerer styrke- og konditionstræning med kostråd, så man kan få en slank og veltrænet krop uden at skulle bruge flere timer hver dag.</p><p>I <a href="https://taarnbybib.dk/da/ting/collection/718500%3A29339309">Mænd, motion og myter</a> skriver Torben Bremann om, hvad der skal til for at få usunde mænd til at skifte livsstil, og dermed, få forøget både livslængde og livskvalitet. Bogen kommer hele vejen rundt om&nbsp;motions- og styrketræning, bækkenbundsøvelser, søvn, afspænding og meditation, kost. I bogen findes konkrete forslag til&nbsp;træning og madopskrifter.</p><p>[[{"type":"media","view_mode":"media_original","fid":"443","attributes":{"alt":"","class":"media-image","height":"431","style":"WIDTH: 100px; HEIGHT: 144px","width":"300"}}]]</p><p>Er du et udemenneske er <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28736185">Motion i naturen&nbsp;</a>af&nbsp;Anne-Marie Olsen og&nbsp;Henriette Bjerre Tybjerg måske noget for dig. Her får du tips til bl.a. styrketræning og familie- og kamplege i naturen.</p><p>På biblioteket kan du i øvrigt også låne en <a href="https://taarnbybib.dk/da/ting/collection/718500%3A90221744">Træningstaske </a>specielt egnet til børnefamilier med hulahopringe, sjippetov og bøger, der kan inspirere jer til at få motion ind i hverdagen på en sjov måde.</p><p style="text-align: right"><a href="#top">Til toppen</a></p><h3><a name="Rygestop">﻿</a></h3><h3>Rygestop</h3><p>[[{"type":"media","view_mode":"media_original","fid":"444","attributes":{"alt":"","class":"media-image","height":"500","style":"WIDTH: 100px; HEIGHT: 159px","width":"315"}}]]</p><p>Tilhører du stadig det rygende mindretal, kan du prøve at sætte en stopper for det med rygestop-klassikeren <a href="https://taarnbybib.dk/da/ting/collection/718500%3A24776484">Endelig ikke-ryger! : den lette vej til rygestop</a> af Allen Carr. Carr har fulgt successen op med en bog målrettet til kvinder: <a href="https://taarnbybib.dk/da/ting/collection/718500%3A25967356">Endelig ikke-ryger for kvinder!</a></p><p>I lydbogen, <a href="https://taarnbybib.dk/da/ting/collection/718500%3A26586488">Vælg røgen fra med meditation og afspænding</a>, kan du høre om, hvordan du kan bruge meditation og afspænding til at blive røgfri, klare abstinenserne og forblive røgfri.</p><p>[[{"type":"media","view_mode":"media_original","fid":"445","attributes":{"alt":"","class":"media-image","height":"521","style":"WIDTH: 100px; HEIGHT: 149px","width":"350"}}]]</p><p>Også Dansk Psykologisk Forlag har udgivet en bog af Maj-Britt Bjerre Koch <a href="https://taarnbybib.dk/da/ting/collection/718500%3A27350135">Rygestop for livet</a>, der beskriver&nbsp;forskellige aspekter af rygeafhængigheden, og hvordan man kommer den til livs.</p><p>Tårnby Kommune tilbyder i øvrigt&nbsp;<a href="http://www.taarnby.dk/sundhed_og_forebyggelse/sundhedscenter_taarnby/tilbud_og_kurser/Rygestop.htm">gratis rygestop-kurser</a> til alle, der bor eller arbejder i Tårnby Kommune.</p><p style="text-align: right"><a href="#top">Til toppen</a></p><h3><a name="parforhold"><font color="#000000">﻿</font></a></h3><h3>Plej dit parforhold</h3><p>Skranter det lidt på hjemmefronten? Så er 2013 måske året, hvor du skal noget ved det. Skilsmisse er dyrt og i øvrigt lever man længere, hvis man lever med en partner.</p><p>Her er nogle bøger, der måske kan inspirere dig til at gøre noget ved det.</p><p>[[{"type":"media","view_mode":"media_original","fid":"446","attributes":{"alt":"","class":"media-image","height":"292","style":"WIDTH: 100px; HEIGHT: 97px","width":"300"}}]]</p><p>I <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28618182">Fem gode minutter med den du elsker</a>, får du&nbsp;100 mindfulnessøvelser, der hjælper dig med at forbedre og forny jeres kærlighed hver dag. Måske skal der slet ikke så meget til?</p><p>[[{"type":"media","view_mode":"media_original","fid":"449","attributes":{"alt":"","class":"media-image","height":"423","style":"WIDTH: 100px; HEIGHT: 141px","width":"300"}}]]</p><p>Mete G. Andersen opfordrer til forebyggelse&nbsp;i sin bog <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28977557">Red dit parforhold - før det kommer i krise</a>. I bogen berøres forskellige livssituationer, der kan udfordre kærligheden såsom sygdom, børn, svigerforældre, sidespring og økonomi.</p><p>[[{"type":"media","view_mode":"media_original","fid":"447","attributes":{"alt":"","class":"media-image","height":"365","style":"WIDTH: 100px; HEIGHT: 122px","width":"300"}}]]</p><p>Er du kvinde og er du allerede kommet derud, hvor du har&nbsp;svært ved at huske, hvad det var, der førte jer sammen, så prøv Ann Watsons <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28736010">Elsk ham igen</a>, hvor hun beskriver, hvordan man&nbsp;kan finde&nbsp;kærligheden frem igen.</p><p>[[{"type":"media","view_mode":"media_original","fid":"450","attributes":{"alt":"","class":"media-image","height":"1441","style":"WIDTH: 100px; HEIGHT: 144px","width":"1000"}}]]</p><p>Jørn Laursen sætter i sin bog, <a href="http://taarnbybib.dk/da/ting/collection/718500%3A27530648">Følelser og erotik i parforholdet</a>, fokus på både følelser, sex og samtale i parforholdet.</p><p>[[{"type":"media","view_mode":"media_original","fid":"451","attributes":{"alt":"","class":"media-image","height":"808","style":"WIDTH: 100px; HEIGHT: 162px","width":"500"}}]]</p><p>Parterapeuten Martin Østergaard har skrevet adskillige bøger om, hvordan mænd og kvinder kan lære at forstå hinanden bedre.&nbsp;Bogen <a href="https://taarnbybib.dk/da/ting/collection/718500%3A27403220">Flere veje til mandens hjerte </a>er bygget op af 117 spørgsmål og svar på, hvordan man som kvinde kan komme tættere på sin mand.</p><p>[[{"type":"media","view_mode":"media_original","fid":"452","attributes":{"alt":"","class":"media-image","height":"500","style":"WIDTH: 100px; HEIGHT: 157px","width":"319"}}]]</p><p>I <a href="https://taarnbybib.dk/da/ting/collection/718500%3A23726416">Vejen til kvindens hjerte</a> henvender Martin Østergaard sig til både mænd og kvinder. Til mænd, der gerne vil være bedre til at give kvinder, hvad de savner, og&nbsp;til kvinder der gerne vil forstås bedre af mænd.</p><p><a href="https://taarnbybib.dk/da/search/ting/dc.subject%3D30.1751?text=&amp;subject=30.1751&amp;facets[]=facet.type%3Abog">Find flere bøger om parforhold her</a></p><p style="text-align: right"><a href="#top">Til toppen</a></p><p>&nbsp;</p><h3><a name="Afspænding">﻿</a></h3><h3>Afspænding</h3><p>Er hverdagen lidt for hektisk og er skuldrene ofte oppe om ørerne, så kan det være, at det er afspænding, der skal hjælpe dig til en bedre hverdag i&nbsp;det nye år. Nogle gange skal der slet ikke så meget til at skabe et pusterum i hverdagen.</p><p>&nbsp;</p><p><u><font color="#0000ff">[[{"type":"media","view_mode":"media_original","fid":"455","attributes":{"alt":"","class":"media-image","height":"415","style":"WIDTH: 100px; HEIGHT: 138px","width":"300"}}]]</font></u></p><p>Apropos pusterum, så beskriver Lotte Paarup i sin bog, Åndedrættet, hvordan man ved at bruge sit åndedræt bevidst gennem øvelser kan hjælpe kroppen til at slappe af.</p><p>&nbsp;</p><p>Klaus Kornø Rasmussen beskriver i <a href="https://taarnbybib.dk/da/ting/collection/718500%3A28194285">Den store bog om afspænding</a>&nbsp;en række fysiske afspændingsøvelser, der involverer hele kroppen.</p><p>Mindfulness har i nogle år nu været en meget populær metode til at fokusere og minimere stress.</p><p><u>[[{"type":"media","view_mode":"media_original","fid":"466","attributes":{"alt":"","class":"media-image","height":"159","style":"WIDTH: 100px; HEIGHT: 119px","width":"134"}}]]</u></p><p>Selvom du har en hektisk hverdag, kan du med&nbsp;David Harps anvisninger i <a href="https://taarnbybib.dk/da/ting/collection/718500%3A29214808">Mindfulness på 5 minutter </a>måske alligevel finde rum til at lave nogle øvelser, der kan hjælpe dig til at sætte tempoet ned.&nbsp;</p><p>&nbsp;</p><p>[[{"type":"media","view_mode":"media_original","fid":"454","attributes":{"alt":"","class":"media-image","height":"458","style":"WIDTH: 100px; HEIGHT: 153px","width":"300"}}]]</p><p>Charlotte i Mandrup beskriver i sin <a href="https://taarnbybib.dk/da/ting/collection/718500%3A26891965">bog om mindfulness</a>, hvordan man gennem bl.a. mindfulness kan arbejde med selv at tage ansvar for egen lykke og være autentisk overfor sig selv. Også i denne bog er der praktiske øvelser, der kan hjælpe lykken på vej.</p><p>Har du lyst til at gå lidt mere fysisk til værks, kan du også låne bibliotekets <a href="https://taarnbybib.dk/da/ting/collection/718500%3A90221752">Træningstaske 2: ny energi til krop og sjæl</a>, der ud over bøger om meditation og mindfulness også indeholder yoga-dvd&acute;er.</p><p><a href="https://taarnbybib.dk/da/search/ting/dc.subject%3D61.36?facets[]=facet.subject:mindfulness&amp;facets[]=-facet.subject%3Amindfulness">Find bøger om mindfulness her</a></p><p style="text-align: right"><a href="#top">Til toppen</a></p>')
              ->teaser('Så fik vi taget hul på det nye år og nu er det tid til at komme i gang med alt det, som vi lovede os selv, før klokken slog 12 den 31. december.')
              ->title('Skal vi så komme i gang med nytårsforsætterne?')
              ->ctime(new \DateTime("-12 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('foo, bar, zoo')
            ->build();
        ;

        $node3 = $service->push(
            new Author($agency['Arhus']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Campaigns',
            'Adult',
            $profile,
            new Params(array(new Editable(1), new Authorship(1)))
        );

        // 4 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>Højtlæsning er godt for mange ting, og så er det&nbsp;også hyggeligt.&nbsp;Her&nbsp;er 7 gode grunde til&nbsp;at læse højt for dit barn.</p><p><strong>1. Nærvær</strong><br />Når du&nbsp;læser højt for dit barn, bliver dit barn fortrolig med bøger og læsning.&nbsp;Højtlæsning er hyggeligt og skaber&nbsp;nærvær.</p><p><strong>2. Nye ord og begreber</strong><br />Barnet lærer nye ord og begreber, når du læser højt. Snak&nbsp;om bogen, forklar ord og leg med dem. Det er lettere at lære ord,&nbsp;når man selv bruger dem.&nbsp;</p><p><strong>3. Genkendelse og indsigt</strong><br />Mange bøger beskriver problemer, som barnet kender fra sig selv.&nbsp;De&nbsp;kan hjælpe barnet&nbsp;med&nbsp;at forstå,&nbsp;hvordan verden hænger sammen og&nbsp;lære dit barn&nbsp;at forstå&nbsp;sine egne og andres følelser</p><p><strong>4. Viden</strong><br />Når du læser højt, får dit barn ny viden. Du er med til at bringe vores kulturarv videre, så dit barn også lærer, hvem Pippi er, og hvorfor Palle er alene i verden.</p><p><strong>5. Fantasi</strong><br />Dit barn får udviklet sin fantasi og kreativitet, når du læser højt. Måske digter det selv&nbsp;en historie eller laver egne rim og remser.</p><p><strong>6. Koncentration</strong><br />Når du læser højt, lærer dit barn at koncentrere sig, og det får fornemmelse af, hvordan&nbsp;en historie starter og slutter.</p><p><strong>7. Læselyst og glæde</strong><br />Børn, der ofte får læst højt, klarer sig generelt bedre i skolen og får lettere ved at lære&nbsp;at læse og skrive.</p><p><strong>Start tidligt&nbsp;og vis begejstring.</strong><br />Når dit&nbsp;barn er&nbsp;8-9 måneder kan du sagtens begynde at&nbsp;læse&nbsp;højt.&nbsp;Find bøger med&nbsp;få og enkle billeder&nbsp;af&nbsp;ting, som barnet kan genkende&nbsp;&nbsp;fra sin egen hverdag.&nbsp;Hav gerne&nbsp;et fast tidspunkt, hvor du læser højt fx ved sengetid.<br /><br />Vis, at du selv er glad for bøger, besøg biblioteket&nbsp;og&nbsp;&nbsp;omgiv jer med&nbsp;bøger&nbsp;derhjemme.&nbsp;Begejstring smitter!</p><p>&nbsp;</p><p>&nbsp;</p>')
              ->teaser('Der er mange gode grunde til at læse højt for dit barn. Her er bare 7 af dem.')
              ->title('7 grunde til at læse højt')
              ->ctime(new \DateTime("-12 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $node4 = $service->push(
            new Author($agency['Kobenhavns']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Literature',
            'Kids',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 5 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<div id="content-inner"><div id="content-main"><div><div><div><div jquery1357993469187="127"><div id="node-7154"><div><p>[[{"type":"media","view_mode":"media_large","fid":"498","attributes":{"alt":"","class":"media-image","height":"192","style":"WIDTH: 100px; HEIGHT: 100px","width":"192"}}]]</p><p>Orlaprisen er børnenes egen bogpris, hvor&nbsp;børnene stemmer på deres personlige favorit. Derefter bliver de tre bøger med flest stemmer sendt videre til en særligt udvalgt børnejury, der vælger hvilken af de tre bøger der fortjener den fornemme Orlapris.</p><p>Der er&nbsp;nomineret 12 spændende børne- og ungdomsbøger til årets Orlapris.&nbsp;Frem til&nbsp;lørdag den 16. marts kan børnene stemme på deres favorit på <a href="http://www.dr.dk/Ramasjang/Orla/orlaprisen2013.htm">Orlaprisens hjemmeside</a>, hvor man også kan høre uddrag af bøgerne, spille Orla-spil og downloade Orla-ringetoner til telefonen.</p><p>Nedenfor kan I se, hvilke bøger, der er nomineret og reservere dem. I kan også kigge og læse i&nbsp;bøgerne i en udstilling på både Hovedbiblioteket og Vestamager Bibliotek.</p></div></div></div></div></div></div></div></div>')
              ->teaser('Vær med til at kåre den bedste børnebog, der blev udgivet i 2012')
              ->title('Orlaprisen 2013')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $node5 = $service->push(
            new Author($agency['Halsnas']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Literature',
            'Kids',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 6 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>Børn lærer sprog, når de er sammen med vigtige personer i deres liv, især deres forældre. Nogle børn er hurtige til at lære at tale, mens andre børn er langsommere. Og for børn i 1-3 års alderen er der meget vide grænser for, hvad der er alderssvarende sprog. En lille gruppe børn har forsinket sprogudvikling eller sproglige vanskeligheder og har behov for særlig støtte til deres sproglige udvikling.<br />Her er en række konkrete forslag til, hvordan du kan være med til at støtte dit barns sproglige udvikling gennem samtaler, leg, bøger, sange, rim og remser.</p><p><strong>Tal med dit barn<br />Tal med dit barn &ndash; og udnyt hverdagens muligheder<br />Leg med dit barn<br />Læs med dit barn<br />Syng og rim med dit barn</strong></p><p><strong>Gode bøger og materialer til børn i 1-3 års alderen:</strong><br />Sprogkufferter (se <a href="http://slagbib.stg.easyting.dk/da/page/sprogkuffert-slagelse-bibliotekerne#overlay=da/node/216/edit" target="_self">liste</a>)<br />Billedbøger med farverige illustrationer &ndash; med eller uden tekst<br />Interaktive bøger med f.eks. flapper, der kan løftes, &rdquo;findebøger&rdquo; eller forskellige materialer, der kan føles&nbsp; på.<br />Bøger med en enkel historie<br />Billedordbøger<br />Bøger med sange, rim og remser<br />Bøger med mange gentagelser</p>')
              ->teaser('Børn lærer sprog, når de er sammen med vigtige personer i deres liv, især deres forældre. Nogle børn er hurtige til at lære at tale, mens andre børn er langsommere. Og for børn i 1-3 års alderen er der meget vide grænser for, hvad der er alderssvarende sprog.')
              ->title('Hjælp dit barn med sproget')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Halsnas']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Other',
            'Adult',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 7 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>Bibliotek.dk er de danske bibliotekers fælles database. Her kan du søge og finde alt hvad der står på bibliotekernes hylder rundt omkring i landet, ligegyldigt om det er på folkebiblioteker eller uddannelsesbiblioteker. Du kan bestille materialerne til afhentning på et af Slagelse Kommunes Biblioteker (Korsør, Skælskør, Slagelse) eller servicepunkter (Dalmose, Vemmelev), hvis du er indmeldt som bruger på Slagelse Bibliotekerne. Du kan se vores servicedeklaration for bibliotek.dk <a href="http://slagbib.stg.easyting.dk/da/news/servicedeklaration-bibliotekdk-0" target="_self">her</a>.</p><p><strong>Søgetips</strong><br />Når du bruger bibliotek.dk kan det være en god idé at kigge på de forprogrammerede søgninger der ligger i venstre spalte på forsiden. Hvis du vælger &rdquo;Film&rdquo; får du f.eks. mulighed for at søge på filmgenrer og på om filmen skal være på blu-ray eller dvd. Hvis du vælger &rdquo;Noder&rdquo;, kan du søge på f.eks. noder for 1 instrument, orkester eller for specifikke instrumenter. Og i &rdquo;Bøger&rdquo; kan du søge på sprog, så hvis du gerne vil træne dit italienske, kan du vælge kun at få vist bøger på italiensk.</p><p><strong>Artikler</strong><br />Du kan få både avis- og tidsskriftartikler fra bibliotek.dk direkte hjem på din computer. Det er &ndash; i de fleste tilfælde &ndash; gratis og meget hurtigere end at skulle vente på at få artiklen hjem på papir.</p><p>Artikler fra tidsskrifter:<br />Når du finder en artikel, som du gerne vil læse, i bibliotek.dk, kan du klikke på [+] ud for artiklens titel for at se om den er tilgængelig elektronisk. Der vil stå &rdquo;Bestil gratis e-kopi&rdquo; med rød skrift til højre på skærmen &ndash; det klikker du på for at bestille artiklen.<br />Første gang du bruger servicen, skal du meldes ind på Statsbiblioteket, som er det bibliotek, der leverer artiklerne. Det er gratis og tager kun et øjeblik.<br />Du kan også komme ud for at der står &rdquo;Bestil kopi mod betaling&rdquo;. Det betyder at du kan bestille en kopi af artiklen på papir, sendt hjem til dig. Det koster typisk 50 øre pr. side artiklen fylder.<br />Du kan læse mere <a href="http://blog.bibliotek.dk/archive/2009/08/25/kopier-af-artikler-i-tidsskrifter" target="_blank">her</a>.</p><p>Artikler fra aviser:<br />For at få avisartikler direkte til din pc, skal du<br />1. være logget ind i bibliotek.dk<br />2. have gemt et af Slagelse Bibliotekerne som dit favoritbibliotek<br />3. have gemt dine personlige data i din profil<br />Det tager lidt tid, men når du har gjort det én gang, skal du fremover bare være logget ind, når du bruger bibliotek.dk, så får du alle mulighederne.<br />Læs om login <a href="http://bibliotek.dk/help.php?brd=hj%E6lp+start&amp;help=login" target="_blank">her</a>.<br />Læs om favoritbiblioteker og personlige data <a href="http://blog.bibliotek.dk/archive/2009/07/31/favoritbiblioteker" target="_blank">her</a>:<br />For at se artiklen skal du klikke på [+] ud for artiklens titel for at få hele posten vist og derefter på den røde tekst &rdquo;&rdquo;Læs artikel fra Infomedia&rdquo;.</p>')
              ->teaser('Bibliotek.dk er de danske bibliotekers fælles database. Her kan du søge og finde alt hvad der står på bibliotekernes hylder rundt omkring i landet, ligegyldigt om det er på folkebiblioteker eller uddannelsesbiblioteker.')
              ->title('Bibliotek.dk - Hvad er det?')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Halsnas']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Other',
            'Young',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 8 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>Med Slagelse Lydavis kan kommunens blinde og svagtseende borgere nemt følge med i de lokale nyheder og informationer.</p><p>Slagelse Bibliotekerne er meget mere end bare bøger. Hver uge udgiver biblioteket &rdquo;Slagelse Lydavis&rdquo;, som bliver lavet til kommunens blinde, svagtseende eller læsehandicappede, som ikke kan læse en trykt avis, eller fx kan følge med i lokale nyheder på avisernes hjemmesider.</p><p>Lydavisen er en cd, hvor informationer fra aviser er indtalt. Den indeholder stof fra lokale aviser som Sjællandske, Ugenyt, Korsør Posten, Skælskør Avis og Søndagsavisen, og er delt op, så den indeholder både kommunal- og lokalstof, officielle meddelelser og navnestof.</p><p><strong>Forskellige informationer fra den forgangne uge</strong><br />Hver uge gennemgås aviserne fra den forgangne uge. Derefter bliver der lavet et manuskript med de vigtigste nyheder og informationer fra hele lokalområdet. Det er både sjovt stof og mere tørre nyheder. Det vil sige alt fra fx dåseræs for børn til borgerindformationer fra kommunen. Der skal være noget til alle.</p><p>En lydavis fylder 90 minutter. Det passer med, at lydavisen indeholder stort set alle vigtige artikler og lokalstof. Det eneste der ikke er fast hver uge er sportsstof, men store sportslige begivenheder eller præstationer kommer næsten altid med.</p><p><strong>Fra almindelige kasettebånd til speciel cd</strong><br />Tidligere var Lydavisen på kassettebånd, men de senere år har Lydavisen været digital. Den bliver redigeret på Slagelse Bibliotekerne, men bliver indlæst og distribueret fra NOTA (tidl. Danmarks Blindebibliotek), og lander i brugeres postkasse hver onsdag.</p><p>Når man er færdig med at lytte til lydavisen kan man blot smide cd&rsquo;en ud, men man kan også gemme den, hvis man fx gerne vil lytte til den på et senere tidspunkt.</p><p><strong>Visitation sker gennem kommunen</strong><br />For at kunne høre lydavisen kræver det en speciel afspiller, som man skal søge om og visiteres til gennem kommunen via Tale-Høre-Syn. Hvis du allerede modtager lydbøger fra NOTA har du allerede afspilleren.</p>')
              ->teaser('Biblioteket samler avisstof til blinde og svagtseende borgere og med Slagelse Lydavis kan kommunens blinde og svagtseende borgere nemt følge med i de lokale nyheder og informationer.')
              ->title('Biblioteket samler avisstof til blinde og svagtseende borgere')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Halsnas']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Facts',
            'Elders',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 9 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p style="text-align: center;">[[{"type":"media","view_mode":"media_large","fid":"244","attributes":{"alt":"","class":"media-image","height":"174","style":"width: 464px; height: 168px;","width":"480"}}]]</p><p>Uanset om du bare ønsker inspiration eller du leder efter noget nyt guf efter at have læst &quot;Twilight&quot;, &quot;Hunger games&quot;,&quot; The mortal instruments&quot;, &quot;Vampire diaries&quot; eller nogle at alle de andre fede serie, så kast et blik på serien &quot;Strange Angels&quot; af Lili St. Crow.</p><p>På dansk hedder serien &quot;Sorte engle&quot;.</p><p><strong>Storyline</strong><br />Dru Anderson lever ikke et helt almindeligt liv. Hendes mor døde, da Dru var en lille pige og siden har Dru rejst USA tyndt sammen med sin far i jagten på forskellige monstrøse skabninger i den såkaldte &quot;real world&quot;. Dru fungerer som sin fars hjælper og besidder visse evner, som hjælper hende til at genkende farer og ikke mindst overleve.</p><p>Historien tager fart den dag Dru&#39;s far kommer hjem efter endnu en jagt, men han dukker op som zombie og for at dræbe Dru! Det lykkes hende at flygte og så går det over stok og sten, for hvem det end er der har gjort faren til zombie, stræber vedkommene også Dru efter livet og mener det alvorligt!</p><p>Dru er en handlekraftig pige der kan nogle tricks, men er her på dybt vand. Heldigvis dukker&nbsp;en uventet hjælp op, som redder Dru&#39;s liv. Det viser sig &nbsp;- ikke overraskende - at Dru heller ikke er en helt almindelig teenage pige, men de små fragmenter hun undervejs afdækker om sig selv, sin oprindelse og sin betydning gør hende ikke klogere eller bringer hende uden for fare - snarere tværtimod!</p><p><br /><strong>Spændende persongalleri</strong><br />Persongalleriet er overskueligt. Dru får revet&nbsp;skolekammeraten, Graves, med&nbsp;ind i det kaos hun kalder sit liv, og de slår sig sammen. Hvad der først drejer sig om ren og skær overlevelse, udvikler sig til et stærkt venskab.</p><p>Graves er en cool halv asiatisk gothdreng, der er vant til at skulle overleve på egen hånd. Han bliver en stærk støtte og allieret for Dru, selvom han knapt aner hvad det er han er blevet rodet ind i.</p><p>Vi møder også Christophe, som er Djamphir. Det vil sige han er halvt menneske, halvt vampyr. Han redder Dru og Graves, men hans motiver er mildest talt uklare og intet er hvad det lader til på overfladen.</p><p>Selvom Dru er en kick ass hovedperson, er hun ikke uovervindelig. Hun famler i blinde og har ikke svaret på alle problemer. Til gengæld har hun en masse bagage med i kraft af moderens tidlige død og faren voldsomme skæbne. Drue står alene i verden og hun er bange og sårbar, selv med sin sarkastiske tilgang til omverdenen, hvilket blot gør hendes person mere troværdig.</p><p><strong>Dansk eller engelsk - du vælger</strong><br />Bog 1 &quot;Strange angels/Sorte engle&quot; ender meget åbent&nbsp;med en ægte&nbsp;cliffhanger. Heldigvis behøver man ikke vente på at læse fortsættelsen, da der allerede er udkommet de første 3 bøger på dansk og på Allerød bibliotekerne har vi også samtlige 5 bøger i serien på engelsk. Du kan reservere serien nedenfor.</p><p>Vil du læse mere om forfatteren eller bøgerne i serien så besøg <a href="http://www.strange-angels.com" target="_self">Strange-angels.com.&nbsp;&nbsp;&nbsp;</a></p>')
              ->teaser('Uanset om du bare ønsker inspiration eller du leder efter noget nyt guf efter at have læst "Twilight", "Hunger games"," The mortal instruments", "Vampire diaries" eller nogle at alle de andre fede serie, så kast et blik på serien "Strange Angels" af Lili St. Crow.')
              ->title('Strange Angels af Lili St. Crow')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Halsnas']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Games',
            'Adult',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 10 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>[[{"type":"media","view_mode":"media_original","fid":"199","attributes":{"alt":"","class":"media-image  media-image-left","height":"471","style":"margin: 8px; width: 240px; height: 377px; float: left;","width":"300"}}]]DCs underforlag Vertigo er kendt for sine lidt vildere serier af høj kvalitet, og sci-fi eposset &quot;Y the Last Man&quot; af forfatteren Brian K. Vaughan og tegneren Pia Guerra er bestemt ingen undtagelse. Vaughan har tidligere stået bag den mesterlige &quot;Pride of Bagdad&quot; og har været en del af det succesfulde forfatterteam bag den frem-ragende mysterie-tvserie &quot;Lost&quot;, så han er bestemt en kompetent herre.&nbsp;</p><h3><br />Fascinerende dystopi</h3><p>&quot;Y The Last Man&quot; handler om Yorick, som efter en voldsom pandemi for nu at bruge an af vor tids hotteste buzzwords, pludselig finder sig i den - på papiret attråværdige situation - at han tilsyneladende er den sidste levende mand tilbage i verden. Faktisk er han det eneste hankønsvæsen, da alle hannerne i dyreriget også er røget i pandemien, hvis man altså lige ser bort fra Yoricks lortekastende abe Ampersand.<br />&nbsp;</p><p>Verden har i den grad ændret sig; infra-sturkturen er brudt sammen, og kvinderne har måttet overtage alle samfunds-funktioner.</p><p>Ingen kender årsagen til pandemien, og ingen ved, hvorfor Yorick og Ampersand har overlevet y-kromosom-kataklysmen.</p><h3>Fare overalt</h3><p>Det er ikke ufarligt at være den sidste mand! Mange forskellige fraktioner vil have fingre i Yorick, nogle for at fuldende naturens hævn og én gang for alle rense jorden for voldtægtsforbrydere og massemordere. Andre vil have fat i ham for at genopbygge menneskebestanden. Yorick selv vil bare gerne til Australien, hvor hans udkårne befinder sig.<br /><br />&quot;Y the Last Man&quot; er en afsluttet serie i 10 bind. Den er både spændende og fascinerende og til tider ret overraskende. Den kan beskrives som en sci-fi road-comic med en spændende, grundlæggende historie og en relevant overvejelse omkring kønnenes roller og betydning i vores samfund.<br />&nbsp;</p>')
              ->teaser('DCs underforlag Vertigo er kendt for sine lidt vildere serier af høj kvalitet, og sci-fi eposset "Y the Last Man" af forfatteren Brian K. Vaughan og tegneren Pia Guerra er bestemt ingen undtagelse.')
              ->title('Y - The Last Man')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Arhus']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Games',
            'Young',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 11 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>[[{"type":"media","view_mode":"media_large","fid":"184","attributes":{"alt":"","class":"media-image  media-image-left","height":"272","style":"margin: 8px; width: 192px; height: 272px; float: left;","width":"192"}}]]Tove Ditlevsens forfatterskab hører blandt de moderne klassikere og oplever for tiden en sand renæssance. Ditlevsens udleverende og selvbiografiske skrivestil debateres og bøgerne genudgives, senest den selvbiografiske bog &quot;Gift&quot; genudgivet i 2012 med forord af Dy Plambeck.&nbsp;</p><p>Tove Ditlevsen levede fra 1917 - 1976. Hun debuterede i 1939 og fik sit folkelige gennembrud i 1943 med romanen &quot;Barndommens gade&quot;, som også er blevet filmatiseret.</p><p>Tove Ditlevsen skrev romaner, lyrik, noveller, eassays og journalistik. Hun var også brevkasse redaktør på ugebladet &quot;Familiejournalen&quot;.</p><p>Mathilde, Anne Linnet og Anne Barfoed har sat musik til Tove Ditlevsens digte.&nbsp;</p><p>Privat var Tove Ditlevsen gift flere gange. Hun fik 4 børn (det ene var adopteret), havde betydelige misbrugsproblemer og endte desværre med at tage sit eget liv. Selvom Tove Ditlevsens forfatterskab rummer smerte, ensomhed og dysterhed, er det også et smukt forfatterskab, af en forfatter der i den grad har magt over sproget og dets virkemidler. Det er også et forfatterskab om Vesterbro, om sammenhold og tilhørsforhold, om kærlighed og kvindeskæbner.</p><p>Læs mere om Tove Ditlevsen på <a href="http://www.forfatterweb.dk/oversigt/zdit/print_zdit">forfatterweb</a>, i <a href="http://www.kvinfo.dk/side/597/bio/493/origin/170/query/Tove%20ditlevsen/">dansk kvindebiografisk leksikon</a>, på <a href="http://www.litteratursiden.dk/forfattere/tove-ditlevsen">litteratursiden</a> eller i <a href="http://www.denstoredanske.dk/Dansk_Biografisk_Leksikon/Kunst_og_kultur/Litteratur/Forfatter/Tove_Ditlevsen?highlight=tove%20ditlevsen">den store danske</a>.</p><p>&nbsp;</p>')
              ->teaser('Tove Ditlevsen')
              ->title('En moderne klassiker')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Arhus']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Book',
            'Elders',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 12 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p><a href="http://www.pallesgavebod.dk/">[[{"type":"media","view_mode":"media_large","fid":"168","attributes":{"alt":"","class":"media-image  media-image-left","height":"125","style":"width: 117px; height: 125px; float: left;","width":"117"}}]]</a><strong><a href="http://www.pallesgavebod.dk/">PALLES GAVEBOD</a></strong></p><p>Pallesgavebod.dk er de danske børnebibliotekers internetunivers.</p><p>Her er de fleste af bibliotekernes mange digitale tilbud samlet under én hat. Hjemmesiden er skabt i et samarbejde mellem <a href="http://www.kulturstyrelsen.dk/de-5-centre/center-for-bibliotek-medier-og-digitalisering/" target="_blank">Kulturstyrelsen - Center for Bibliotek, Medier&nbsp;og Digitalisering</a> under Kulturministeriet og fortællevirksomheden <a href="http://www.copenhagenbombay.dk/about" target="_blank">Copenhagen Bombay</a>, der har stået for koncept, design og udvikling af hele projektet. IT-virksomhederne <a href="http://www.konform.com/" target="_blank">Konform</a> og <a href="http://www.dbc.dk/" target="_blank">DBC</a> har som underleverandører stået for programmering af backend, frontend og databasesystemer.</p>')
              ->teaser('Pallesgavebod.dk er de danske børnebibliotekers internetunivers.')
              ->title('Palles gavebod')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Arhus']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Campaigns',
            'Adult',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // 13 -------------------------------
        $builder = $this->createResourceBuilder();
        $resource = $builder
              ->body('<p>Resident Evil spilserien er en af de mest populære spilserier gennem tiden.<br />De mange fans elsker de uhyggelige og actionmættede spil, hvor man bekæmper horder af zombier og andre monstre.</p><p>I &quot;Resident Evil: Operation Raccoon City&quot; får man som spiller mulighed for at se hændelserne fra tidligere spil fra en helt ny vinkel. Denne gang er man nemlig tilbage i Raccoon City og spiller soldat hos Umbrella Security Service. Ens teams opgave er at finde T-virusset og slette alle spor som knytter Umbrella sammen med det dødbringende T-virus.</p><p>Men livet som Umbrella soldat er ingen dans på roser! US Special Forces er også på jagt efter T-virusset og byen er fyldt med sultne zombier!</p><p>På Allerød Bibliotek kan du låne spillet til PS3. du kan også bestille det nedenfor.</p>')
              ->teaser('Resident Evil spilserien nægter ligesom zombierne i spillet at dø. Du kan låne det seneste skud på stammen "Resident Evil: Operation Raccoon City" tll PS3 på Allerød Bibliotek.')
              ->title('Resident Evil: Operation Raccoon City')
              ->ctime(new \DateTime("-22 day"))
              ->copyleft(new Copyleft(''))
        ;

        $builder = new ProfileBuilder();
        $profile = $builder
            ->yearwheel(new Yearwheel('Summer'))
            ->tags('bar, zoo')
            ->build();
        ;

        $service->push(
            new Author($agency['Arhus']->getAgencyId(), 1, 'Lastname', 'Name'),
            $resource,
            'Themes',
            'Adult',
            $profile,
            new Params(array(new Editable(1), new Authorship(0)))
        );

        // Add some fake history.
        $log = array();
        $log[] = new History(
          $node2,
          $agency['Halsnas']->getAgencyId(),
          new \DateTime("2013-05-01 15:26:55"),
          'syndicate'
        );
        $log[] = new History(
          $node3,
          $agency['Halsnas']->getAgencyId(),
          new \DateTime("2013-05-02 11:11:11"),
          'syndicate'
        );
        $log[] = new History(
          $node5,
          $agency['Arhus']->getAgencyId(),
          new \DateTime("2013-05-03 15:01:27"),
          'syndicate'
        );

        foreach ($log as $l) {
          $manager->persist($l);
        }
        $manager->flush();
    }

    public static function createCategories(ObjectManager $manager)
    {
        $categories = array(
            'Other',
            'Event',
            'Music',
            'Facts',
            'Book',
            'Film',
            'Literature',
            'Themes',
            'Markdays',
            'Games',
            'Campaigns',
        );

        foreach ($categories as $category) {
            $category = new Category($category);
            $manager->persist($category);
        }
        $manager->flush();
    }

    public static function createAudiences(ObjectManager $manager)
    {
        $audiences = array(
            'All',
            'Adult',
            'Kids',
            'Young',
            'Elders',
        );
        foreach ($audiences as $audience) {
            $audience = new Audience($audience);
            $manager->persist($audience);
        }
        $manager->flush();
    }
}
