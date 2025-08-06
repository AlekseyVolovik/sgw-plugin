<?php declare(strict_types=1);

namespace SGW\Api;

use Couchbase\User;

class ApiProvider {
    public CompetitionsApi $competitions;
    public CompetitorsApi $competitors;
    public ContentApi $content;
    public CountriesApi $countries;
    public EventsApi $events;
    public HealthCheckApi $healthcheck;
    public LeagueApi $league;
    public LocationApi $location;
    public MatchCentreApi $matchcentre;
    public OddsCentreApi $oddscentre;
    public PlayersApi $players;
    public PredictionTablesApi $predictiontables;
    public ProjectsApi $projects;
    public SportsApi $sports;
    public StatisticsApi $statistics;
    public UsersApi $users;
    public VenuesApi $venues;
    public bool $status;

    public function __construct() {
        $this->competitions = new CompetitionsApi();
        $this->competitors = new CompetitorsApi();
        $this->content = new ContentApi();
        $this->countries = new CountriesApi();
        $this->events = new EventsApi();
        $this->healthcheck = new HealthCheckApi();
        $this->league = new LeagueApi();
        $this->location = new LocationApi();
        $this->matchcentre = new MatchCentreApi();
        $this->oddscentre = new OddsCentreApi();
        $this->players = new PlayersApi();
        $this->predictiontables = new PredictionTablesApi();
        $this->projects = new ProjectsApi();
        $this->sports = new SportsApi();
        $this->statistics = new StatisticsApi();
        $this->users = new UsersApi();
        $this->venues = new VenuesApi();

        $this->status = $this->healthcheck->getHealthCheck();
    }
}