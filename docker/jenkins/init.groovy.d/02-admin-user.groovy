import jenkins.model.*
import hudson.security.*

def instance = Jenkins.getInstanceOrNull()
if (instance == null) {
    println "Jenkins not ready — skip admin setup"
    return
}

// Only run while Jenkins is still open (no login required)
if (instance.isUseSecurity()) {
    println "Security already configured — skip admin setup"
    return
}

def user = System.getenv("JENKINS_ADMIN_USER") ?: "admin"
def pass = System.getenv("JENKINS_ADMIN_PASSWORD") ?: "admin123"

def realm = new HudsonPrivateSecurityRealm(false)
realm.createAccount(user, pass)
instance.setSecurityRealm(realm)

def strategy = new FullControlOnceLoggedInAuthorizationStrategy()
strategy.setAllowAnonymousRead(false)
instance.setAuthorizationStrategy(strategy)
instance.save()

println "EqualVoice Jenkins: created user '${user}' and enabled security"
