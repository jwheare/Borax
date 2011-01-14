<?php

namespace App\Controller;
use Core\Controller;
use Core\HttpStatus;
use Core\Url;
use Core\Twitter, Core\TwitterException;
use App\Model;
use Exception;

class TwitterHtml extends Controller\Html {
    public function start () {
        // Step 1 - Get request token
        // http://oauth.net/core/1.0a/#auth_step1
        try {
            $callback = null;
            if ($next = $this->request->postget('next')) {
                $callback = Url::addHost($this->request->getSession()->addNext('/twitter/callback', $next));
            }
            $twitter = new Twitter();
            $requestTokenParams = $twitter->getRequestToken($this->request, $callback);
        } catch (TwitterException $e) {
            throw HttpStatus\Base::mapAuthException($e);
        }
        
        // Store request token and secret in the session
        $token = $requestTokenParams['oauth_token'];
        $secret = $requestTokenParams['oauth_token_secret'];
        $this->session->set('twitter_oauth_token', $token);
        $this->session->set('twitter_oauth_token_secret', $secret);
        
        // Step 2 - Set Twitter credentials and redirect to auth URL
        // http://oauth.net/core/1.0a/#auth_step2
        $twitter->setCredentials($token, $secret);
        throw new HttpStatus\Found($twitter->getAuthorizationUrl(true));
    }
    public function callback () {
        // Abort if denied
        if ($this->request->get("denied")) {
            $this->session->message('twitterDenied');
            throw $this->session->finalRedirect();
        }
        
        // Retrieve request token and secret from the session
        $session_oauth_token = $this->session->delete('twitter_oauth_token');
        $session_oauth_token_secret = $this->session->delete('twitter_oauth_token_secret');
        if (!$session_oauth_token || !$session_oauth_token_secret) {
            throw new HttpStatus\BadRequest("Missing Twitter session credentials");
        }
        
        // Verify request token
        $authToken = $this->request->get("oauth_token");
        $authTokenVerifier = $this->request->get("oauth_verifier");
        if (!$authToken) {
            throw new HttpStatus\BadRequest('Missing oauth_token parameter in Twitter callback.');
        }
        if ($authToken !== $session_oauth_token) {
            throw new HttpStatus\BadRequest("Mismatched Twitter auth tokens: $authToken / $session_oauth_token");
        }
        
        // Step 3 - Exchange request token stored in the session for an oAuth token and secret.
        // http://oauth.net/core/1.0a/#auth_step3
        try {
            $twitter = new Twitter();
            $twitter->setCredentials($session_oauth_token, $session_oauth_token_secret);
            $accessTokenParams = $twitter->getAccessToken($authTokenVerifier);
        } catch (TwitterException $e) {
            throw HttpStatus\Base::mapAuthException($e);
        }
        
        // Store access token and secret in the session
        $finalToken = $accessTokenParams['oauth_token'];
        $finalSecret = $accessTokenParams['oauth_token_secret'];
        $authedTwitterUserId = $accessTokenParams['user_id'];
        $authedTwitterUserName = $accessTokenParams['screen_name'];
        
        // Now we decide what to do with our brand new token
        
        // a) Already logged in: done
        if ($this->session->isLoggedIn()) {
            throw $this->session->finalRedirect();
        }
        
        // b) Existing user linked to this Twitter account: Save Twitter account details and sign in
        $authedPerson = new Model\Person();
        if ($authedPerson->loadByTwitter_Id($authedTwitterUserId)) {
            $authedPerson->twitter_name = $authedTwitterUserName;
            $authedPerson->setTwitterCredentials($finalToken, $finalSecret);
            $authedPerson->save();
            
            throw $this->session->signIn($authedPerson);
        }
        
        // c) No account: Create and link
        $newPerson = new Model\Person();
        $newPerson->twitter_id = $authedTwitterUserId;
        $newPerson->twitter_name = $authedTwitterUserName;
        $newPerson->setTwitterCredentials($finalToken, $finalSecret);
        $newPerson->save();
        
        // Sign in and throw the final redirect
        throw $this->session->signIn($newPerson);
    }
}
